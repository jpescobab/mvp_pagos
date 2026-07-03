## Context

No existe hoy `UserController`, rutas `/usuarios`, ni permisos granulares para gestionar usuarios: solo un permiso único `usuarios.administrar` (seeder `RolesAndPermissionsSeeder`) y un `UserPolicy` que lo referencia en sus cinco métodos estándar (`viewAny/view/create/update/delete`), sin estar conectado a ningún controlador todavía. El modelo `Funcionario` ya existe (`rut`, `nombre`, `user_id` nullable, `ccosto_id` nullable, `cfinanciero_id` nullable, `activo`) pero no se usa en ningún lugar del código — se creó en una tarea anterior de tablas maestras institucionales y quedó sin consumidor.

La jerarquía institucional (`instituciones -> jurisdicciones -> cfinancieros -> ccostos`) no se relaciona directamente con `users`; se llega a ella a través de `Funcionario.cfinanciero` y `Cfinanciero.jurisdiccion`.

## Goals / Non-Goals

**Goals:**
- Listado de usuarios con búsqueda, filtros institucionales, paginación, orden y acciones de cuenta (activar/desactivar/reset password) con autorización granular en backend.
- Reutilizar `Funcionario` como fuente de datos institucionales del usuario en vez de duplicar columnas en `users`.
- Reemplazar el permiso monolítico `usuarios.administrar` por una matriz granular auditable por acción.

**Non-Goals:**
- CRUD completo (crear/editar usuario), vista de detalle, asignación de roles, CRUD de roles/permisos, importación masiva, exportación: quedan fuera de este cambio. Sus acciones de menú se muestran deshabilitadas con "Disponible próximamente" cuando el permiso está presente, sin rutas ni páginas nuevas.
- Permisos directos a usuarios (fuera de roles) — explícitamente prohibido por el template de la tarea.
- Cambiar cómo `Funcionario.activo` gobierna el estado institucional del funcionario (ej. si sigue contratado) — es un concepto distinto de `users.active` (si la cuenta puede iniciar sesión).

## Decisions

**1. `users.active` separado de `funcionarios.activo`.** `users.active` gobierna la capacidad de iniciar sesión (lo que las acciones Activar/Desactivar de este cambio controlan); `funcionarios.activo` es el estado institucional del funcionario (contratado/vigente), preexistente y fuera de alcance. Alternativa descartada: reutilizar `funcionarios.activo` para bloquear login — mezclaría dos conceptos (vigencia institucional vs. acceso al sistema) y dejaría sin forma de desactivar el acceso de un usuario sin `Funcionario` asociado (ej. cuentas técnicas).

**2. Datos institucionales via `Funcionario`, no columnas nuevas en `users`.** `rut`, `cargo`, `unidad`, jurisdicción/centro financiero/centro de costo se leen de `funcionario()->cfinanciero->jurisdiccion` y `funcionario()->ccosto`. Se agrega `User::funcionario(): HasOne` (inversa de `Funcionario::user()`). Un usuario sin `Funcionario` vinculado (ej. cuenta de sistema) muestra esos campos como `null`; el frontend los renderiza como "—". Alternativa descartada: duplicar `rut/cargo/unidad` directamente en `users` — rompe la fuente única de verdad que ya estableció la tarea de tablas maestras al crear `Funcionario`.

**3. Reemplazo (no adición) de `usuarios.administrar`.** Se confirmó que solo `UserPolicy` y `RolesAndPermissionsSeederTest` referencian ese permiso; ningún controlador activo lo usa (no hay `UserController` aún). Se reemplaza limpiamente por `usuarios.ver/crear/editar/activar/desactivar/resetear_password/asignar_roles`, todos asignados a `superadmin` y `admin` (mismo comportamiento efectivo). Alternativa descartada (mantener ambos): dejaría un permiso "fantasma" sin ningún punto de chequeo real, violando la regla del harness de no dejar código/datos sin propósito claro.

**4. `GestionUsuariosService` para las reglas de activar/desactivar/reset password.** Controlador liviano; el servicio centraliza las reglas críticas (no auto-desactivación, no desactivar al último `superadmin`/`admin` activo — el template dice "último Administrador del Sistema", que se interpreta como el último usuario activo con rol `admin` o `superadmin`, ya que no existe un rol `Administrador del Sistema` como tal en el catálogo actual de roles) y la generación de contraseña temporal (usa `Illuminate\Support\Str::password()`, se persiste solo con hash vía el cast `password => hashed` ya definido en `User`, se retorna en texto plano una única vez en la respuesta JSON de Inertia — nunca se loguea ni se audita en claro).

**5. Auditoría vía `AuditLogger` genérico existente.** No se crea infraestructura nueva; cada acción (`activar_usuario`, `desactivar_usuario`, `resetear_password_usuario`) llama a `AuditLogger::log()` con el actor, el usuario afectado, y el estado antes/después de `active` (para reset password, `after` no incluye la contraseña, solo `must_change_password => true`).

**6. Filtro "Rol" usa los roles existentes de Spatie tal cual están seedeados** (hoy `admin`, `superadmin`) — no se crea un catálogo de roles nuevo ni una UI de gestión de roles.

**7. Última acceso (`last_login_at`).** Se actualiza mediante un listener del evento `Illuminate\Auth\Events\Login` de Fortify (no en el controlador de usuarios) — es responsabilidad del flujo de autenticación, no del listado.

## Risks / Trade-offs

- [Cambiar el nombre del permiso rompe cualquier autorización futura que asumiera `usuarios.administrar`] → Mitigación: confirmado que hoy no hay consumidores activos fuera de `UserPolicy`/su test; ambos se actualizan en el mismo cambio.
- [Interpretación de "último Administrador del Sistema" como "último usuario activo con rol admin o superadmin" puede no coincidir exactamente con una futura intención de negocio más fina] → Mitigación: la regla se centraliza en `GestionUsuariosService::puedeDesactivar()`, fácil de ajustar sin tocar el controlador ni el frontend si se define un rol dedicado más adelante.
- [Acciones de menú deshabilitadas ("Ver detalle", "Editar", "Asignar roles") pueden generar la sensación de una UI incompleta] → Mitigación: es una decisión de alcance explícita (confirmada con el usuario); el tooltip "Disponible próximamente" comunica que es intencional, no un bug.

## Migration Plan

1. Migraciones: `add_active_last_login_at_must_change_password_to_users_table`, `add_cargo_unidad_to_funcionarios_table`. Ambas aditivas (columnas nullable o con default), sin downtime.
2. Actualizar `RolesAndPermissionsSeeder` y volver a correr `php artisan db:seed --class=RolesAndPermissionsSeeder` en cada entorno (idempotente vía `firstOrCreate`/`syncPermissions`); el permiso viejo `usuarios.administrar` queda huérfano en la tabla `permissions` tras el reseed — se elimina explícitamente en el propio seeder (`Permission::where('name', 'usuarios.administrar')->delete()`) para no dejar basura.
3. Sin rollback especial: son cambios aditivos de esquema y de datos de permisos, reversibles revirtiendo la migración y re-seedeando el permiso anterior si fuera necesario.

## Open Questions

Ninguna — decisiones de alcance ambiguas (rutas diferidas, reemplazo de permisos) ya confirmadas con el usuario antes de este documento.
