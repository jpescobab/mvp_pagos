## Why

La definición de informe razonado (`definiciones_informe_razonado`: `codigo`, `nombre`, `descripcion`, `activo`) es el catálogo de tipos de informe del módulo de reportabilidad. Hoy es una entidad "solo crear" y, peor, su creación no está autorizada: `DefinicionInformeRazonadoController::store` no llama a `Gate::authorize` y su Form Request `CrearDefinicionInformeRazonadoRequest` no define `authorize()` (default `true`). El listado, en cambio, sí exige `informes.ver`. El resultado es que **cualquier usuario autenticado puede crear una definición aunque no tenga permiso ni para verlas** — inconsistente con su propio índice y con el resto del core (usuarios, roles, tablas maestras), donde la escritura siempre pasa por una policy.

A ese hueco de autorización se suman cuatro carencias: el `codigo` no valida unicidad (se pueden crear dos definiciones con el mismo código), no hay pantalla de detalle ni de edición ni forma de desactivar una definición, la `DefinicionInformeRazonadoPolicy` solo tiene `viewAny`/`view` (sin `create`/`update`/`delete`), y el índice en React no sigue el patrón de listado denso (`text-green-600` hardcodeado, sin búsqueda, sin menú de acciones).

## What Changes

- **Cerrar el hueco de autorización de escritura**: introducir el permiso `informes.administrar`; exigirlo en `store` (`Gate::authorize('create', ...)` + `authorize()` en el Form Request) y en las nuevas acciones `edit`/`update`/`destroy`. **BREAKING** (a propósito): crear una definición pasa a requerir `informes.administrar`; el comportamiento anterior (cualquier autenticado podía crear) era el defecto que se corrige.
- **Nuevo permiso `informes.administrar`** sembrado en `WorkflowInformesRazonadosSeeder` junto a `informes.aprobar`/`informes.publicar` y otorgado al rol `admin`. No se toca `RolesAndPermissionsSeeder` (así no cambia el test de lista exacta de permisos core).
- **Unicidad de `codigo`**: validada en creación y en edición (ignorando la propia definición al editar).
- **Completar el CRUD**: `show` (detalle de la definición y sus ejecuciones), `edit`/`update`, y `destroy` con protección ante ejecuciones asociadas (una definición con ejecuciones no se elimina). Agregar `create`/`update`/`delete` a la policy.
- **Índice al patrón de listado denso** de `tema-visual-layout`: búsqueda con debounce, badge de estado con tokens `success`/`danger`, menú de acciones en dropdown, paginación. La creación deja de ser un formulario incrustado en el índice y pasa a su propia pantalla `create`, como el resto de las entidades administrables.
- **Auditoría**: al ser una tabla maestra del módulo, la definición registra creación/edición/eliminación en `audit_logs` vía el trait `RegistraAuditoria` (ya en `master`).

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `gestionar-informes-razonados`: el requirement "Crear una definición de informe razonado" cambia de "cualquier usuario autenticado" a exigir el permiso `informes.administrar` y unicidad de `codigo`. Se agregan requirements para ver el detalle de una definición, editarla, y desactivarla/eliminarla con protección ante ejecuciones asociadas, además del listado denso con búsqueda.

## Impact

**Código modificado**

- `app/Http/Controllers/InformesRazonados/DefinicionInformeRazonadoController.php`: `Gate::authorize` en `store`; nuevos `create`, `show`, `edit`, `update`, `destroy`; `index` con búsqueda y paginación.
- `app/Http/Requests/InformesRazonados/CrearDefinicionInformeRazonadoRequest.php`: `authorize()` + unicidad de `codigo`.
- `app/Policies/DefinicionInformeRazonadoPolicy.php`: `create`, `update`, `delete`.
- `app/Models/DefinicionInformeRazonado.php`: trait `RegistraAuditoria`.
- `database/seeders/WorkflowInformesRazonadosSeeder.php`: permiso `informes.administrar` → rol `admin`.
- `routes/informes-razonados.php`: rutas `create`/`show`/`edit`/`update`/`destroy` de definiciones.
- `resources/js/pages/informes-razonados/definiciones/`: `index` reescrito (denso), nuevas `create`/`show`/`edit`.
- Wayfinder regenerado.

**Código nuevo**

- `app/Http/Requests/InformesRazonados/ActualizarDefinicionInformeRazonadoRequest.php`
- `tests/Feature/InformesRazonados/*Definicion*Test.php`

**Hallazgo adyacente, explícitamente fuera de alcance**

`EjecucionInformeRazonadoController::store` (iniciar ejecución) tiene el **mismo** hueco de autorización: ni `Gate::authorize` ni `authorize()` en `IniciarEjecucionInformeRazonadoRequest`. Se deja fuera de este change a propósito: iniciar una ejecución es una acción operacional distinta (elaborar un informe a partir de un corte publicado, que luego pasa por revisión humana antes de publicarse) y merece su propio permiso y su propio análisis; reutilizar `informes.administrar` por conveniencia sería modelar mal. Queda como el siguiente gap del módulo.

**Sin impacto en**: el workflow de ejecuciones (`InformeRazonadoService`, transiciones, snapshots), los permisos `informes.ver`/`informes.aprobar`/`informes.publicar`, ni el resto de dominios. El módulo de informes razonados sigue siendo activable sin cambios.
