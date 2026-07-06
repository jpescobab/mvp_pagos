## Why

`Cfinanciero` y `Ccosto` son los dos últimos niveles de la jerarquía institucional fija (`instituciones -> jurisdicciones -> cfinancieros -> ccostos`) que siguen siendo de solo lectura en la UI — mismo estado en el que estaban `Item`/`Proveedor`/`ClienteMedidor` antes de sus respectivos changes. El usuario confirmó explícitamente que esta jerarquía SÍ debe poder editarse libremente desde la UI (no es de solo lectura por diseño). Sus menús de acciones (`CfinancieroActionsMenu`/`CcostoActionsMenu`) siguen siendo el placeholder "Disponible próximamente".

## What Changes

- Backend: se completan `CfinancieroPolicy`/`CcostoPolicy` (ya tienen `viewAny`; se agregan `view`/`create`/`update`/`delete`, reutilizando `core_institucional.administrar`), Form Requests de alta/edición, y se agregan `create/store/show/edit/update/destroy` a ambos controladores (los `index()` actuales, ya gateados con `Gate::authorize('viewAny', ...)`, no cambian). Rutas nuevas en `routes/maestros.php` bajo los prefijos `cfinancieros`/`ccostos` ya registrados.
- `Cfinanciero` y `Ccosto` **no tienen columna `deleted_at`** (a diferencia de `Item`/`Proveedor`/`ClienteMedidor`) — `destroy` hace **hard delete**, consistente con el esquema actual; no se agrega una migración de soft deletes no pedida.
- `destroy` de `Ccosto` bloquea si tiene `clientes_medidores`, `procesos_adquisicion` o `funcionarios` asociados (verificado por FK real en las migraciones). `destroy` de `Cfinanciero` bloquea si tiene `ccostos` o `funcionarios` asociados — esto ya está anticipado por el requirement existente "No se puede eliminar un nivel de la jerarquía con hijos asociados" en `core-institucional-capj`, hoy solo garantizado por la restricción de FK a nivel de base de datos; este change agrega la verificación a nivel de aplicación para dar un mensaje de error entendible en vez de dejar pasar la excepción SQL cruda.
- Frontend: `create.tsx`/`edit.tsx` con formulario plano — `Cfinanciero` con `<Select>` de `jurisdiccion_id` (requerido, `Jurisdiccion::all()`); `Ccosto` con `<Select>` de `cfinanciero_id` (requerido, `Cfinanciero::all()`) y campo opcional `cod_edificio`. `show.tsx` de detalle para ambos. `CfinancieroActionsMenu`/`CcostoActionsMenu` pasan de placeholder a acciones reales (ver/editar/eliminar), calcando `ItemActionsMenu`.
- Tests Feature de CRUD para ambos, siguiendo la convención ya usada para `Item`/`ClienteMedidor`.
- **Sin migraciones nuevas.**

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `core-institucional-capj`: el requirement "Mantener códigos institucionales únicos" ya anticipa que no se puede eliminar un nivel con hijos asociados (hoy solo a nivel de restricción de FK); se agrega el requirement de que el sistema exponga un CRUD administrable (HTTP + UI) sobre `cfinancieros`/`ccostos`, con esa misma verificación hecha explícita a nivel de aplicación.

## Impact

- `app/Policies/{Cfinanciero,Ccosto}Policy.php`: agregan `view`/`create`/`update`/`delete`.
- `app/Http/Requests/Maestros/{Store,Update}{Cfinanciero,Ccosto}Request.php` (nuevo)
- `app/Http/Controllers/Maestros/{Cfinanciero,Ccosto}Controller.php`: agregan los 6 métodos de mutación.
- `routes/maestros.php`: rutas nuevas.
- `resources/js/pages/maestros/{cfinancieros,ccostos}/{create,edit,show}.tsx` (nuevo)
- `resources/js/components/maestros/{cfinanciero,ccosto}-actions-menu.tsx`: pasan de placeholder a acciones reales.
- `tests/Feature/Maestros/{Store,Show,Update,Destroy}{Cfinanciero,Ccosto}Test.php` (nuevo)
- `openspec/specs/core-institucional-capj/spec.md`: delta.
