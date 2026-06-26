## Why

La tarea 3 del harness (`tasks/03_implementar_seguridad_roles_permisos.md`) requiere autenticación gestionada por admin/superadmin, roles y permisos con Spatie Permission, y auditoría de acciones sensibles. Hoy no existe ningún mecanismo de roles/permisos ni de auditoría — cualquier usuario autenticado tiene acceso igual a todo, y no hay registro de accesos denegados ni de cambios sensibles.

## What Changes

- Instala y configura `spatie/laravel-permission`; `App\Models\User` usa el trait `HasRoles`.
- Migraciones nuevas: `audit_logs` (auditoría general, polimórfica, con `before`/`after`/`metadata`) y `security_audit_logs` (eventos de seguridad: accesos denegados, etc.). Ambas append-only (solo `created_at`, sin `updated_at`).
- Servicio `AuditLogger` con API genérica (`log()` para auditoría general, `logSecurityEvent()` para eventos de seguridad).
- Hook `Gate::after()` en `AppServiceProvider`: toda autorización denegada (Policy o Gate) se registra automáticamente en `security_audit_logs` — cubre el escenario "Acción no permitida" del spec sin tocar cada controlador.
- Bypass `Gate::before()`: el rol `superadmin` tiene acceso total, sin necesidad de asignarle cada permiso individualmente.
- Seeder de roles y permisos: roles `superadmin` y `admin`; permisos `usuarios.administrar`, `roles.administrar`, `core_institucional.administrar`, `tablas_maestras.administrar` — **solo para los dominios que ya existen** (tareas 1 y 2). `admin` recibe todos salvo `roles.administrar` (solo `superadmin` administra roles/permisos). El usuario de prueba sembrado (`test@example.com`) recibe el rol `superadmin`.
- `Policies`: `UserPolicy` y `RolePolicy`, gatilladas por los permisos anteriores.
- **No se conecta** el escenario "Auditar cambio de estado" del spec (transición de workflow) — depende de `WorkflowTransitionService`, que no existe hasta la tarea 5. La tarea 5 llamará a este mismo `AuditLogger` cuando se construya.
- **No se construye UI** de gestión de usuarios/roles en React — esta tarea es backend (igual que tareas 1 y 2); la UI se aborda cuando se construya el panel admin.

## Capabilities

### New Capabilities

- `seguridad-auditoria`: formaliza el spec libre existente (`openspec/specs/seguridad-auditoria/spec.md`) al formato estructurado de OpenSpec — parcialmente, dejando explícito qué escenario queda conectado ahora y cuál depende de la tarea 5.

### Modified Capabilities

(ninguna)

## Impact

- Nueva dependencia: `spatie/laravel-permission`.
- 2 migraciones nuevas (`audit_logs`, `security_audit_logs`) + las que publica Spatie (`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`).
- Nuevos modelos: `AuditLog`, `SecurityAuditLog`.
- Nuevo servicio: `app/Services/AuditLogger.php`.
- Nuevas policies: `app/Policies/UserPolicy.php`, `app/Policies/RolePolicy.php`.
- `app/Models/User.php`: agrega `HasRoles`.
- `app/Providers/AppServiceProvider.php`: agrega `Gate::before` (bypass superadmin) y `Gate::after` (log de seguridad).
- `database/seeders/DatabaseSeeder.php`: agrega el seeder de roles/permisos y la asignación de rol al usuario de prueba.
- No afecta tablas ni modelos de las tareas 1 y 2.
