## Why

Un proceso de adquisición no se puede editar una vez creado: `ProcesoAdquisicionService` solo tiene `crear()`, y `ProcesoAdquisicionController` expone únicamente `index`/`create`/`store`/`show`. Un proceso recién creado en estado `borrador` con un error en código, objeto, monto, proveedor o modalidad queda inservible: hay que crear otro. Esto obliga a datos basura y contradice el flujo normal de trabajo (corregir antes de enviar a revisión).

## What Changes

- Nuevo permiso `adquisiciones.editar_proceso`, sembrado en `WorkflowAdquisicionesSeeder` y asignado a `admin` y `administrativo_adquisiciones`.
- `ProcesoAdquisicionPolicy::update()` autoriza con `adquisiciones.editar_proceso`. `superadmin` sigue con acceso vía `Gate::before`.
- **Regla de negocio**: un proceso solo es editable en estado `borrador`. Una vez enviado a revisión ya es registro/evidencia; para corregirlo se usa la transición existente `devolver_a_borrador`. La invariante la valida el Service (excepción de dominio si el estado no es `borrador`) y la refleja la UI (el acceso a editar solo aparece en `borrador` con permiso).
- Nuevo `ProcesoAdquisicionService::actualizar()`: transaccional, valida modalidad activa (igual que `crear()`), actualiza el `ProcesoAdquisicion` y **sincroniza el `Proceso` asociado** (`modalidad_id`, `monto`) — necesario porque el checklist documental se resuelve leyendo esos campos desde el `Proceso`.
- Controller liviano `edit`/`update` (delegan en el Service), `FormRequest` `ActualizarProcesoAdquisicionRequest` (código `unique` ignorando el propio registro), rutas `procesos.edit`/`procesos.update` y regeneración de Wayfinder.
- UI: página `editar.tsx` (espejo de `crear.tsx`, precargada) y botón "Editar" en `show.tsx` visible solo en `borrador` y con permiso.
- Tests de dominio, HTTP y de sincronización del checklist.

## Capabilities

### New Capabilities
<!-- Ninguna capability nueva: se extiende el comportamiento de capabilities existentes. -->

### Modified Capabilities
- `adquisiciones`: se agrega el requerimiento de que un `proceso_adquisicion` se pueda actualizar solo en estado `borrador`, sincronizando el `Proceso` asociado (`modalidad_id`/`monto`) para que el checklist documental refleje los cambios.
- `api-adquisiciones`: se agregan rutas HTTP autenticadas para editar (`edit`) y actualizar (`update`) un proceso de adquisición, gobernadas por `adquisiciones.editar_proceso`, delegando la lógica en `ProcesoAdquisicionService::actualizar()`.
- `paginas-adquisiciones`: se agrega la página/formulario de edición y el punto de entrada "Editar" en el detalle, visible solo cuando el proceso está en `borrador` y el usuario tiene el permiso.

## Impact

- **Service**: `app/Services/Adquisiciones/ProcesoAdquisicionService.php` (nuevo `actualizar()`), `app/Exceptions/ProcesoAdquisicionException.php` (nuevo caso "no editable en estado").
- **Policy**: `app/Policies/ProcesoAdquisicionPolicy.php` (nuevo `update()`).
- **HTTP**: `app/Http/Controllers/Adquisiciones/ProcesoAdquisicionController.php` (`edit`/`update`), `app/Http/Requests/Adquisiciones/ActualizarProcesoAdquisicionRequest.php` (nuevo), `routes/adquisiciones.php` (2 rutas).
- **Seeder / permisos**: `database/seeders/WorkflowAdquisicionesSeeder.php` (permiso nuevo + roles). Caché de permisos invalidada (`PermisosCompartidosResolver`).
- **Frontend**: `resources/js/pages/adquisiciones/procesos/editar.tsx` (nuevo), `resources/js/pages/adquisiciones/procesos/show.tsx` (botón Editar), Wayfinder regenerado (`resources/js/routes/adquisiciones/**`, `resources/js/actions/**`). El `ProcesoAdquisicionResource` puede exponer un flag `puede_editar` o la UI lo deriva de `estado_actual.codigo` + `auth.permissions`.
- **Tests**: `tests/Feature/Adquisiciones/*` (edición, sincronización de checklist, 403 sin permiso, rechazo fuera de `borrador`, `codigo` unique propio).
- **Sin migraciones ni cambios de esquema.** Sin impacto en integraciones externas ni en el workflow/transiciones.
