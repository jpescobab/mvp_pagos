## Why

El change anterior (`administrar-items-presupuestarios`, archivado) implementó el CRUD de `Item` pero dejó explícitamente diferido el de `Asignacion` y `Catalogo` — sus tablas hijas (`item_id`), ya modeladas en `openspec/specs/tablas-maestras-institucionales/spec.md` (requirement "Modelar el clasificador presupuestario institucional") y con modelo + migración ya existentes en el código, pero sin ninguna forma de administrarlas. Sin esto, el clasificador presupuestario queda a medias: se puede crear un `Item`, pero no las asignaciones ni los catálogos que cuelgan de él.

## What Changes

- Backend: `AsignacionController` y `CatalogoController` (namespace `Maestros`), cada uno con `store`/`update`/`destroy` anidados bajo su `Item` padre (`/maestros/items/{item}/asignaciones[/{asignacion}]`, `/maestros/items/{item}/catalogos[/{catalogo}]`) — sin rutas `index`/`create`/`edit`/`show` propias, porque sus datos se muestran dentro del detalle del ítem padre (mismo patrón ya usado por `FacturaController` anidado bajo `casos/{caso}/facturas`).
- `AsignacionPolicy`/`CatalogoPolicy` reutilizando `core_institucional.administrar` (igual que `ItemPolicy`), Form Requests con la misma validación (`codigo` único, `nombre` requerido, `descripcion` opcional, `activo` booleano).
- `ItemController::show` pasa a cargar (`with`) las relaciones `asignaciones` y `catalogos` del ítem y exponerlas en la respuesta Inertia.
- Frontend: `resources/js/pages/maestros/items/show.tsx` gana dos secciones nuevas ("Asignaciones", "Catálogos"), cada una con lista + formulario inline de alta + acciones de editar/eliminar por fila — mismo patrón visual de sección (`rounded-xl border p-4`, lista `divide-y`, formulario inline) ya usado por la sección "Facturas" de `casos/show.tsx`, extendido con editar/eliminar (que Facturas no tiene, por ser de solo alta).
- Sin páginas nuevas de listado/alta/edición independientes para Asignación/Catálogo — todo vive dentro del detalle del ítem.
- Tests Feature de CRUD para ambos modelos, siguiendo la convención ya usada para `Item`.
- **Sin migraciones nuevas**: `asignaciones` y `catalogos` ya existen con el esquema necesario.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `tablas-maestras-institucionales`: el requirement "Modelar el clasificador presupuestario institucional" ya tiene los escenarios de registrar asignación/catálogo bajo su ítem; se agregan los escenarios de edición, eliminación (con bloqueo si aplica) y de que ambos se administran desde el detalle del ítem padre, no como catálogos independientes.

## Impact

- `app/Http/Controllers/Maestros/{Asignacion,Catalogo}Controller.php` (nuevo)
- `app/Policies/{Asignacion,Catalogo}Policy.php` (nuevo)
- `app/Http/Requests/Maestros/{Store,Update}{Asignacion,Catalogo}Request.php` (nuevo)
- `app/Http/Resources/Maestros/{Asignacion,Catalogo}Resource.php` (nuevo)
- `app/Http/Controllers/Maestros/ItemController.php`: `show()` carga `asignaciones`/`catalogos`.
- `app/Http/Resources/Maestros/ItemResource.php`: expone `asignaciones`/`catalogos` cuando la relación viene cargada.
- `routes/maestros.php`: rutas nuevas anidadas bajo `items/{item}`.
- `resources/js/pages/maestros/items/show.tsx`: dos secciones nuevas.
- `resources/js/types/maestros.ts`: tipos `Asignacion`/`Catalogo`.
- `tests/Feature/Maestros/*{Asignacion,Catalogo}Test.php` (nuevo)
- `app/Providers/AppServiceProvider.php`: registro de las dos policies nuevas.
