## Context

`UserController` ya tiene `create`/`store` (alta con `CrearUsuarioRequest` + `GestionUsuariosService::crear()`) y un método privado `catalogos()` que arma roles/jurisdicciones/centros para los formularios. `user-actions-menu.tsx` lista "Editar usuario" entre las acciones diferidas (deshabilitadas). El formulario de alta (`create.tsx`) define el estilo a seguir.

## Goals / Non-Goals

**Goals:**
- Editar datos personales (name, email) e institucionales (rut, cargo, unidad, cfinanciero, ccosto) de un usuario existente.
- Sanear el caso legado de usuarios sin `Funcionario` (creándolo al editar).

**Non-Goals:**
- Asignar/cambiar roles (acción separada con permiso `usuarios.asignar_roles`, cambio futuro).
- Cambiar contraseña (ya existe "Resetear contraseña"), activar/desactivar (ya existen), ver detalle.

## Decisions

- **Sin roles en el formulario de edición.** El diseño de permisos granulares separa `usuarios.editar` de `usuarios.asignar_roles`; mezclar roles en la edición rompería esa separación y duplicaría la futura acción "Asignar roles". El service NO toca roles en `editar()`.
- **`EditarUsuarioRequest` separado de `CrearUsuarioRequest`** en vez de un request compartido con condicionales: las reglas difieren en dos puntos estructurales (sin `roles`, `unique` con `ignore()`) y el proyecto ya usa un FormRequest por acción (`CrearEgresoCguRequest`, etc.).
- **`unique` con `ignore()`**: email ignora `users.id` del usuario editado; rut ignora el `funcionarios.id` de su funcionario (si existe). Permite guardar sin cambiar esos campos.
- **`updateOrCreate` implícito del Funcionario**: si el usuario no tiene funcionario (usuario seedeado del starter kit), `editar()` lo crea con los datos del formulario — mismo shape que en `crear()`. Evita un estado no editable.
- **Auditoría before/after con los valores previos completos** de los campos editables (patrón de `activar`/`desactivar` que guarda `before`/`after`), para trazabilidad de qué cambió.
- **PATCH `/usuarios/{usuario}`** consistente con las rutas existentes del grupo (`PATCH .../activar`, `PATCH .../desactivar`).

## Risks / Trade-offs

- [Riesgo] Editar el email de un usuario cambia su identidad de login sin notificarlo → Mitigación: queda auditado con before/after; la notificación al usuario queda fuera de alcance y puede proponerse aparte si se requiere.
- [Riesgo] Regenerar Wayfinder sin `--with-form` rompe las variantes `.form` usadas por otras páginas (ya ocurrió) → Mitigación: usar siempre `php artisan wayfinder:generate --with-form`.
- [Riesgo] Dos funcionarios apuntando al mismo user si se crea en paralelo → Mitigación: la creación va dentro de la misma `DB::transaction()` y parte de la relación `hasOne` existente.
