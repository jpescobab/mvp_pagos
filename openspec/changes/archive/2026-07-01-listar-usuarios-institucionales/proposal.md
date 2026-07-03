## Why

No existe ninguna vista administrativa para consultar los usuarios institucionales del sistema: no hay `UserController`, no hay rutas `/usuarios`, y los permisos granulares que gobernarían sus acciones tampoco existen (solo hay un permiso único `usuarios.administrar`). El modelo `Funcionario` (rut, cargo institucional vía `cfinanciero`/`ccosto`, vínculo a `User`) ya se creó en una tarea anterior pero no se usa en ningún lugar del código. Sin este listado, el Administrador del Sistema no tiene forma de ver quién tiene acceso, con qué rol, ni de activar/desactivar cuentas o resetear contraseñas sin entrar directamente a la base de datos.

## What Changes

- Nuevo `UserController@index` (namespace `App\Http\Controllers\Seguridad`) que lista usuarios con búsqueda (nombre/email/rut), filtros (estado, rol, jurisdicción, centro financiero, centro de costo), paginación configurable (15/25/50/100) y orden (activos primero, luego nombre asc por defecto; ordenable por nombre/email/estado/último acceso/fecha de creación).
- Acciones inline sobre cada usuario, cada una detrás de su propio permiso y validada en backend (`TransicionWorkflowService` no aplica aquí: estos no son estados de workflow, son atributos de cuenta):
  - Activar / Desactivar cuenta (`PATCH /usuarios/{id}/activar`, `PATCH /usuarios/{id}/desactivar`), con las reglas críticas de no auto-desactivación y de no dejar sin Administrador del Sistema activo al último que tenga ese rol.
  - Resetear contraseña (`POST /usuarios/{id}/reset-password`): genera contraseña temporal, la retorna una única vez en la respuesta (nunca se persiste en texto plano ni se audita en claro), marca `must_change_password`.
- **Nuevas columnas**: `users.active` (bool, default true), `users.last_login_at` (timestamp nullable, actualizado en login vía evento Fortify), `users.must_change_password` (bool, default false); `funcionarios.cargo` y `funcionarios.unidad` (string nullable). Se reutiliza `Funcionario` (ya vinculado a `User`, `Cfinanciero`, `Ccosto`) como fuente de rut/cargo/unidad/jurisdicción (derivada vía `cfinanciero.jurisdiccion`)/centro financiero/centro de costo — no se duplican estos datos en `users`.
- Reemplazo del permiso único `usuarios.administrar` por la matriz granular `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.activar`, `usuarios.desactivar`, `usuarios.resetear_password`, `usuarios.asignar_roles` (mismo set efectivo para `superadmin`/`admin`, ahora auditable por acción). **BREAKING**: código o tests que referencien `usuarios.administrar` deben actualizarse (`UserPolicy`, `RolesAndPermissionsSeederTest`).
- `UserPolicy` actualizada a los permisos granulares; se agregan métodos de policy para activar/desactivar/resetear contraseña/asignar roles. `delete()` queda forzado a `false` (eliminación física de usuarios prohibida por el harness).
- Frontend: página `resources/js/pages/seguridad/usuarios/index.tsx` con tabla (desktop), vista simplificada (tablet) y cards (móvil); filtros, paginación, menú de acciones por fila, estados vacíos y skeleton de carga. Laravel envía `permissions` (capabilities) calculadas server-side; React solo las lee, nunca las calcula.
- Fuera de alcance (diferido a tareas futuras): CRUD completo (formularios de creación/edición), vista de detalle, CRUD de roles/permisos, asignación de roles, importación masiva, exportación. Las acciones "Ver detalle", "Editar usuario" y "Asignar roles" se muestran en el menú **deshabilitadas** con indicación "Disponible próximamente" cuando el usuario tiene el permiso correspondiente — no se crean rutas ni páginas para ellas en este cambio.
- Auditoría: activar, desactivar y resetear contraseña se registran vía `AuditLogger` (servicio genérico ya existente); no se crea infraestructura de auditoría nueva.

## Capabilities

### New Capabilities

- `listar-usuarios-institucionales`: listado administrativo de usuarios con búsqueda, filtros, paginación, orden, y acciones protegidas por permiso (activar, desactivar, resetear contraseña) sobre `users`/`funcionarios`.

### Modified Capabilities

- `seguridad-auditoria`: se reemplaza el permiso `usuarios.administrar` por la matriz granular `usuarios.ver/crear/editar/activar/desactivar/resetear_password/asignar_roles`.

## Impact

- `app/Models/User.php` (relación `funcionario()`, casts `active`/`must_change_password`).
- `app/Models/Funcionario.php` (columnas `cargo`, `unidad` en `$fillable`).
- Nuevas migraciones: alter `users` (active, last_login_at, must_change_password), alter `funcionarios` (cargo, unidad).
- Nuevo `app/Http/Controllers/Seguridad/UserController.php`, `app/Http/Resources/Seguridad/UserResource.php`.
- Nuevo `app/Services/Seguridad/GestionUsuariosService.php` (reglas de activar/desactivar/reset password, incluida la protección del último Administrador del Sistema).
- `app/Policies/UserPolicy.php` actualizada.
- `database/seeders/RolesAndPermissionsSeeder.php` actualizado (permisos granulares).
- Nueva `routes/seguridad.php`: rutas `usuarios.*` agregadas al archivo existente.
- Nuevo `resources/js/pages/seguridad/usuarios/index.tsx` y componentes en `resources/js/components/seguridad/` (filtros, tabla, menú de acciones, badge de estado, paginación).
- `resources/js/types/seguridad.ts` (tipos `UsuarioListado`, `FiltrosUsuarios`, etc.).
- `tests/Feature/Seguridad/RolesAndPermissionsSeederTest.php` actualizado; nuevos tests en `tests/Feature/Seguridad/UserControllerTest.php`.
