## Context

Hoy los roles (`superadmin`, `admin`) y sus permisos existen únicamente en `database/seeders/RolesAndPermissionsSeeder.php` (y en los seeders de cada módulo funcional, que agregan sus propios permisos a esos mismos roles). No hay controlador ni página que exponga un CRUD sobre `roles` o `role_has_permissions`. La asignación de roles a un usuario ocurre una sola vez, en `GestionUsuariosService` durante la creación (ver `UserController::store()`); no existe una acción para cambiarle los roles a un usuario ya creado.

Ya existen piezas reutilizables:
- `app/Policies/RolePolicy.php`, gateando por `roles.administrar`.
- El patrón de controlador+página de `app/Http/Controllers/Seguridad/UserController.php` + `resources/js/pages/seguridad/usuarios/` (filtros, paginación, permisos granulares, uso de `AuditLogger`).
- El agrupamiento visual por módulo ya usado en `resources/js/components/app-sidebar.tsx` y `resources/js/components/nav-group.tsx`.

## Goals / Non-Goals

**Goals:**
- CRUD de roles (listar, crear, editar, eliminar) gobernado por `roles.administrar`.
- Asignación de permisos a un rol vía checklist agrupado por módulo.
- Reasignar roles a un usuario existente, gobernado por el permiso ya existente `usuarios.asignar_roles`.
- Auditar toda mutación (crear/editar/eliminar rol, reasignar roles de usuario) vía `AuditLogger`.
- Guardas de integridad: no eliminar `superadmin`/`admin`; no eliminar un rol con usuarios asignados.

**Non-Goals:**
- No se construye un sistema de permisos dinámico: los permisos siguen siendo un catálogo fijo definido en código (seeders), no creables desde la UI. La UI de roles solo permite *asignar* permisos existentes a un rol, no *crear* permisos nuevos.
- No se toca el flujo de creación de usuario (`UserController::store()`) más allá de reutilizar el servicio de asignación de roles si aplica.
- No se migra ni se reemplaza Spatie Laravel Permission.

## Decisions

- **Un solo permiso para el CRUD de roles**: se reutiliza `roles.administrar` (ya definido y sin uso) para listar/crear/editar/eliminar rol, en vez de granularizar como se hizo con `usuarios.*`. Razón: los roles son un recurso administrativo único de bajo volumen (a diferencia de usuarios, que tiene alto volumen y necesita acciones auditables por separado); granularizar aquí agregaría permisos sin beneficio de auditoría diferenciado, ya que cualquier persona con `roles.administrar` puede razonablemente hacer las cuatro operaciones.
- **Reasignar roles de un usuario reutiliza `usuarios.asignar_roles`** (ya definido en el requirement "Permisos granulares para gestionar usuarios institucionales"), en vez de crear un permiso nuevo — la spec ya contemplaba esta acción, solo faltaba la UI/ruta que la usara.
- **Catálogo de permisos para el checklist**: se obtiene con `Permission::all()` agrupado por el prefijo antes del primer `.` (mismo criterio que la convención `modulo_accion.verbo`), y se mapea a las mismas etiquetas de módulo que usa el sidebar (General, Administración, Pago de Proveedores, Adquisiciones, Maestros, Reportabilidad, Integraciones) mediante un mapa fijo prefijo→módulo en el backend (no en React), para que el checklist no dependa de hardcodear nada en el frontend.
- **Guarda de eliminación de rol con usuarios asignados**: se bloquea sin excepción (no hay "confirmación explícita" con reasignación en cascada) — si un rol tiene usuarios, el admin debe reasignarles el rol primero desde la edición de usuario, y luego eliminar el rol vacío. Evita lógica de reasignación masiva implícita y mantiene el cambio acotado.
- **`GestionUsuariosService` se extiende, no se duplica**: la reasignación de roles a un usuario existente se agrega como método nuevo en el mismo servicio (p. ej. `asignarRoles(User $usuario, array $roles)`), reutilizando la validación de "no dejar sin roles a un usuario" si ya existe un chequeo análogo para desactivación.

## Risks / Trade-offs

- [Riesgo: un admin podría auto-eliminarse todos sus permisos de gestión de roles] → Mitigación: si el usuario autenticado edita el rol que le da `roles.administrar` y se quita ese permiso a sí mismo, se le permite (es una decisión legítima del superadmin), pero la policy sigue evaluándose en cada request, así que perdería acceso inmediatamente; se documenta como comportamiento esperado, no se bloquea.
- [Riesgo: colisión con permisos que agregan los seeders de módulos funcionales en caliente] → Mitigación: el listado de permisos disponibles en el checklist se lee en vivo de la tabla `permissions`, no de un catálogo hardcodeado, así que siempre refleja lo que los seeders ya crearon.
- [Trade-off: no granularizar permisos de roles] → Aceptado explícitamente arriba; si en el futuro se necesita diferenciar "quién puede eliminar roles" de "quién puede crearlos", será un cambio incremental de permisos, no una reescritura.

## Migration Plan

- Sin migraciones de esquema (las tablas de Spatie ya existen). Solo cambios de código: controlador, rutas, páginas, tests. Despliegue estándar sin pasos especiales.

## Open Questions

Ninguna pendiente — el alcance quedó acotado en la sesión de propuesta (roles/permisos únicamente; tablas maestras institucionales quedan para un change posterior).
