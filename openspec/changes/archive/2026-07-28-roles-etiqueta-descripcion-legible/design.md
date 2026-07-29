## Context

`roles` es la tabla estándar de Spatie (`id`, `name`, `guard_name`, timestamps). La app usa `Spatie\Permission\Models\Role` directamente (sin modelo custom). El CRUD de roles pasa por `RoleController` + `GestionRolesService` (que ya usa `forceFill(['name' => ...])` para escribir), con `StoreRoleRequest`/`UpdateRoleRequest`. El `name` crudo se muestra en `seguridad/roles/index.tsx`, en el selector de roles de `seguridad/usuarios/edit.tsx` y en los badges de `seguridad/usuarios/show.tsx`.

## Goals / Non-Goals

**Goals:**
- Dar a cada rol un nombre legible (`etiqueta`) y una `descripcion`, editables y sembrados.
- Mostrar la etiqueta en toda la superficie de gestión/asignación, con fallback al `name`.
- Cero regresiones: sin permisos/roles nuevos, sin tocar gating.

**Non-Goals:**
- No se introduce un modelo `App\Models\Role` custom ni se cambia `config/permission.php`.
- No se hace obligatoria la etiqueta (retrocompatibilidad).
- No se cambia el `name` técnico de ningún rol (sigue siendo la clave de negocio en seeders/tests/gating).
- No se traduce el `name` en gating ni en `auth.permissions`.

## Decisions

**1. Columnas nullables en `roles`, sin modelo custom.**
Migración agrega `etiqueta` (string, nullable) y `descripcion` (string 500, nullable). Eloquent expone columnas como atributos aunque no estén en `$fillable`, así que la lectura (`$role->etiqueta`) funciona sin tocar el modelo de Spatie. La escritura se hace con `forceFill` en `GestionRolesService` (consistente con cómo ya escribe `name`), evitando depender del `$guarded` de Spatie.

**2. Fallback en la UI (`etiqueta ?? name`), no en la BD.**
La etiqueta queda nullable; cada componente que hoy muestra `name` pasa a mostrar `etiqueta ?? name`. Así, roles legados o creados por API sin etiqueta siguen mostrándose. El `name` técnico se muestra como dato secundario en mono en el índice y el editor de roles, para que siga siendo visible (es la clave que usan seeders y gating).

**3. Backfill vía seeders idempotentes, no en la migración.**
Los seeders que crean roles (`RolesAndPermissionsSeeder` para superadmin/admin, `WorkflowPagoProveedoresSeeder`, `WorkflowInformesRazonadosSeeder`, `WorkflowAdquisicionesSeeder`) setean `etiqueta`/`descripcion` al crear cada rol (`firstOrCreate` + `forceFill`/update). Re-correr un seeder actualiza las etiquetas de los roles ya sembrados (idempotente). Se evita hardcodear nombres de negocio en una migración de datos. Nota operativa: en entornos ya poblados hay que re-correr los seeders para el backfill (igual que con un permiso nuevo).

**4. Requests validan campos opcionales.**
`StoreRoleRequest`/`UpdateRoleRequest` agregan `etiqueta` => `['nullable','string','max:255']` y `descripcion` => `['nullable','string','max:500']`. `GestionRolesService::crear/editar` extienden su contrato de datos para persistirlos; `editar` sigue invalidando la caché de `PermisosCompartidosResolver` (aunque etiqueta/descripcion no afecten permisos, no cambia el flujo existente).

## Risks / Trade-offs

- **`$guarded` de Spatie**: en vez de arriesgar mass-assignment sobre el modelo de Spatie, se usa `forceFill` — patrón ya presente en `editar()`. Bajo riesgo.
- **Backfill manual**: los entornos existentes muestran el `name` (fallback) hasta re-correr seeders. Aceptable y no rompe nada; se documenta.
- **`descripcion` string(500)**: si a futuro se necesita texto largo, migrar a `text` es trivial. Se elige string por ser una etiqueta de una línea.
- **Auditoría**: `GestionRolesService` audita `crear_rol`/`editar_rol`; se incluye `etiqueta`/`descripcion` en el before/after para no perder trazabilidad del cambio de metadatos.
