## 1. Instalación y configuración

- [x] 1.1 `composer require spatie/laravel-permission` y publicar/ejecutar su migración.
- [x] 1.2 Agregar el trait `HasRoles` a `app/Models/User.php`.

## 2. Auditoría

- [x] 2.1 Migración `create_audit_logs_table`: id, user_id (nullable FK -> users, nullOnDelete), action, auditable_type (nullable), auditable_id (nullable), before (json nullable), after (json nullable), metadata (json nullable), created_at (sin updated_at).
- [x] 2.2 Migración `create_security_audit_logs_table`: id, user_id (nullable FK -> users, nullOnDelete), event, description (nullable), ip_address (nullable), user_agent (nullable), metadata (json nullable), created_at (sin updated_at).
- [x] 2.3 Modelos `app/Models/AuditLog.php` y `app/Models/SecurityAuditLog.php` (relación polimórfica `auditable()` en `AuditLog`; `const UPDATED_AT = null` en ambos).
- [x] 2.4 Servicio `app/Services/AuditLogger.php`: método `log(string $action, ?Model $auditable, array $before, array $after, array $metadata)` y `logSecurityEvent(string $event, ?string $description, array $metadata)`.

## 3. Roles y permisos

- [x] 3.1 Seeder `database/seeders/RolesAndPermissionsSeeder.php`: crea roles `superadmin`/`admin` y permisos `usuarios.administrar`, `roles.administrar`, `core_institucional.administrar`, `tablas_maestras.administrar`; asigna todos menos `roles.administrar` a `admin`.
- [x] 3.2 Asignar el rol `superadmin` al usuario de prueba sembrado en `DatabaseSeeder.php`.
- [x] 3.3 Encadenar el nuevo seeder en `DatabaseSeeder.php`, antes de crear el usuario de prueba.

## 4. Autorización

- [x] 4.1 `app/Providers/AppServiceProvider.php`: `Gate::before` (bypass total para `superadmin`) y `Gate::after` (registra en `security_audit_logs` cuando el resultado es denegado).
- [x] 4.2 `app/Policies/UserPolicy.php`: `viewAny`/`view`/`create`/`update`/`delete` gatillados por `usuarios.administrar`.
- [x] 4.3 `app/Policies/RolePolicy.php`: `viewAny`/`view`/`create`/`update`/`delete` gatillados por `roles.administrar`.
- [x] 4.4 Registrar ambas policies (`AuthServiceProvider`/`AppServiceProvider` según corresponda en Laravel 13).

## 5. Tests

- [x] 5.1 Test: el seeder crea los roles/permisos esperados; `admin` no tiene `roles.administrar`.
- [x] 5.2 Test: un usuario con rol `superadmin` pasa cualquier autorización sin permiso asignado explícitamente.
- [x] 5.3 Test: un usuario sin permiso es denegado por `UserPolicy`/`RolePolicy` y queda un registro en `security_audit_logs`.
- [x] 5.4 Test: `AuditLogger::log()` crea un registro en `audit_logs` con los datos esperados (before/after/metadata).

## 6. Validación

- [x] 6.1 Ejecutar `php artisan migrate:fresh --seed` contra PostgreSQL y confirmar que el usuario de prueba queda con rol `superadmin`.
- [x] 6.2 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
