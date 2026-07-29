## 1. Migración y persistencia

- [x] 1.1 Migración que agrega `etiqueta` (string, nullable) y `descripcion` (string 500, nullable) a la tabla `roles`.
- [x] 1.2 `StoreRoleRequest` y `UpdateRoleRequest`: agregar reglas `etiqueta` => `['nullable','string','max:255']` y `descripcion` => `['nullable','string','max:500']`.
- [x] 1.3 `GestionRolesService::crear` y `editar`: extender el contrato de datos y persistir `etiqueta`/`descripcion` con `forceFill`, incluyéndolos en el before/after de la auditoría. `editar` conserva la invalidación de caché existente.

## 2. Exponer en el backend hacia React

- [x] 2.1 `RoleController::index`: incluir `etiqueta` y `descripcion` en el payload de cada rol.
- [x] 2.2 `RoleController::edit`: incluir `etiqueta` y `descripcion` del rol en el payload.
- [x] 2.3 `UserController::edit`: el listado de roles disponibles (`Role::orderBy('name')->get([...])`) debe incluir `etiqueta` para el selector.

## 3. Seeders (backfill de etiquetas)

- [x] 3.1 Setear `etiqueta`/`descripcion` al crear cada rol en `RolesAndPermissionsSeeder` (superadmin, admin), `WorkflowPagoProveedoresSeeder`, `WorkflowInformesRazonadosSeeder` (los tres roles dedicados + los que cree) y `WorkflowAdquisicionesSeeder`. Idempotente: al re-correr, actualiza la etiqueta del rol ya existente. Revisar también `FuncionariosCapjSeeder` por si crea roles.

## 4. Frontend

- [x] 4.1 Tipos TS de rol: agregar `etiqueta: string | null` y `descripcion: string | null` donde se tipe el rol (índice/edición de roles y selector de usuarios).
- [x] 4.2 `seguridad/roles/index.tsx`: mostrar `etiqueta ?? name` como principal, `name` en mono como secundario, y `descripcion` truncada (patrón de listado denso).
- [x] 4.3 `seguridad/roles/create.tsx` y `edit.tsx`: inputs para `etiqueta` y `descripcion`; enviar en el submit.
- [x] 4.4 `seguridad/usuarios/edit.tsx`: el selector de roles muestra `etiqueta ?? name`.
- [x] 4.5 `seguridad/usuarios/show.tsx`: los badges de rol muestran `etiqueta ?? name` (mantener el `name` como key).

## 5. Tests

- [x] 5.1 Feature: crear un rol con `etiqueta`/`descripcion` los persiste; editar un rol los actualiza (y audita el cambio). Extender los tests existentes de `GestionRolesTest` si aplica.
- [x] 5.2 Feature: `RoleController::index` y `edit` exponen `etiqueta`/`descripcion`; `UserController::edit` incluye `etiqueta` en los roles disponibles.
- [x] 5.3 Confirmar que `RolesAndPermissionsSeederTest` sigue verde (no se agregan permisos).

## 6. Validación y cierre

- [x] 6.1 `vendor/bin/pint --dirty --format agent`, `php artisan test` (suite Seguridad), `npm run types:check`, `npm run lint:check`.
- [x] 6.2 Regenerar Wayfinder si cambian rutas (no se agregan); `npm run build` sin errores.
