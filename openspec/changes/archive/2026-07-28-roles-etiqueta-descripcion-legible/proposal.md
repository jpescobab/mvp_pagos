## Why

La tabla `roles` es la estándar de Spatie (`id`, `name`, `guard_name`, timestamps): no tiene un nombre legible ni descripción. Toda la UI muestra el `name` crudo en snake_case (`elaborador_informes`, `gestor_reportabilidad`, `administrativo_finanzas`), tanto en el índice de roles como en el selector de roles al editar un usuario y en los badges del detalle de usuario. Para quien asigna roles, eso es poco claro: no comunica qué hace cada rol ni en qué se diferencian.

## What Changes

- Se agregan dos columnas nullables a `roles`: `etiqueta` (nombre legible, p. ej. "Elaborador de informes") y `descripcion` (una línea sobre qué habilita el rol).
- La UI muestra la `etiqueta` como texto principal y el `name` técnico como secundario (mono), con **fallback al `name`** cuando `etiqueta` es nula — retrocompatible, ningún rol queda sin mostrar.
- Los formularios de crear/editar rol suman los campos `etiqueta` y `descripcion` (opcionales); las Form Requests los validan.
- Los seeders que crean roles setean `etiqueta`/`descripcion`; al re-ejecutarse (idempotentes) hacen backfill de los roles ya sembrados.
- Se muestra la `etiqueta` en: índice de roles, selector de roles del editor de usuario, y badges del detalle de usuario.

## Capabilities

### New Capabilities
<!-- Ninguna. -->

### Modified Capabilities
- `seguridad-auditoria`: extiende la administración de roles para que cada rol tenga un nombre legible y una descripción, usados en la UI de gestión de roles y de asignación a usuarios.

## Impact

- **Migración**: agrega `etiqueta` y `descripcion` (nullables) a `roles`. Sin backfill acoplado a nombres de rol en la migración; el backfill lo hacen los seeders (idempotentes).
- **Backend**: `GestionRolesService::crear/editar` persisten `etiqueta`/`descripcion` (vía `forceFill`, como ya hace con `name`); `StoreRoleRequest`/`UpdateRoleRequest` los validan (nullable string); `RoleController::index/edit` los exponen. Seeders (`RolesAndPermissionsSeeder`, `WorkflowPagoProveedoresSeeder`, `WorkflowInformesRazonadosSeeder`, `WorkflowAdquisicionesSeeder`, y donde se creen roles) setean las etiquetas.
- **Frontend**: `seguridad/roles/{index,create,edit}.tsx`, `seguridad/usuarios/{edit,show}.tsx`, y los tipos TS de rol.
- **Sin modelo custom de Role**: las columnas se leen como atributos Eloquent y se escriben con `forceFill`; no se cambia `config/permission.php`.
- **Sin permisos ni roles nuevos**, sin tocar el gating ni el workflow. `RolesAndPermissionsSeederTest` (afirma la lista exacta de permisos) no se ve afectado.

### Decisiones abiertas para revisión humana

- **(a)** `descripcion` como `string(500)` (una línea larga) vs `text`. Se propone `string(500)`.
- **(b)** `etiqueta` **opcional con fallback al `name`** (retrocompatible) vs obligatoria para roles nuevos. Se propone opcional + fallback.
- **(c)** Backfill de etiquetas vía **seeders idempotentes** (requiere re-correr seeders) vs un data-migration con las etiquetas hardcodeadas. Se propone seeders, para no acoplar nombres de negocio a una migración.
