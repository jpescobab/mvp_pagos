## 1. Backend: autorización

- [x] 1.1 Agregar a `app/Policies/CfinancieroPolicy.php`: `view`/`create`/`update`/`delete` chequeando `core_institucional.administrar` (ya tiene `viewAny`, no tocarlo).
- [x] 1.2 Agregar a `app/Policies/CcostoPolicy.php`: `view`/`create`/`update`/`delete` chequeando `core_institucional.administrar` (ya tiene `viewAny`, no tocarlo). También agregué las relaciones `clienteMedidores()`/`procesosAdquisicion()`/`funcionarios()` a `Ccosto` y `funcionarios()` a `Cfinanciero` (no existían, necesarias para el chequeo de `destroy`).
- [x] 1.3 Crear `app/Http/Requests/Maestros/StoreCfinancieroRequest.php`/`UpdateCfinancieroRequest.php`: `authorize()` chequea `core_institucional.administrar`; `rules()`: `codigo` (`required`, `string`, `max:255`, `unique:cfinancieros,codigo` / ignorando el propio en update), `nombre` (`required`), `jurisdiccion_id` (`required`, `exists:jurisdicciones,id`), `activo` (`boolean`).
- [x] 1.4 Crear `app/Http/Requests/Maestros/StoreCcostoRequest.php`/`UpdateCcostoRequest.php`: `authorize()` chequea `core_institucional.administrar`; `rules()`: `codigo` (`required`, `string`, `max:255`, `unique:ccostos,codigo` / ignorando el propio en update), `nombre` (`required`), `cfinanciero_id` (`required`, `exists:cfinancieros,id`), `cod_edificio` (`nullable`, `string`), `activo` (`boolean`).

## 2. Backend: controladores y rutas

- [x] 2.1 Agregar a `CfinancieroController`: `create()` (carga `Jurisdiccion::all()` mapeada a `{id, codigo, nombre}`), `store()`, `show()`, `edit()` (misma carga), `update()`, `destroy()` (bloquea solo si `ccostos()->exists()` — `funcionarios` usa `nullOnDelete`, no bloquea, según design.md).
- [x] 2.2 Agregar a `CcostoController`: `create()` (carga `Cfinanciero::all()` mapeada a `{id, codigo, nombre}`), `store()`, `show()`, `edit()` (misma carga), `update()`, `destroy()` (bloquea si `clienteMedidores()->exists()` o `procesosAdquisicion()->exists()` — `funcionarios` usa `nullOnDelete`, no bloquea).
- [x] 2.3 Agregar las rutas `crear`/`store`/`show`/`edit`/`update`/`destroy` en `routes/maestros.php` bajo los prefijos `cfinancieros`/`ccostos` ya existentes.

## 3. Frontend

- [x] 3.1 Regenerar los helpers de Wayfinder (`php artisan wayfinder:generate --with-form`).
- [x] 3.2 Crear `resources/js/pages/maestros/cfinancieros/create.tsx`/`edit.tsx`: formulario plano con `codigo`, `nombre`, `<Select>` de `jurisdiccion_id`, `activo`.
- [x] 3.3 Crear `resources/js/pages/maestros/cfinancieros/show.tsx`: detalle con acciones editar/eliminar.
- [x] 3.4 Crear `resources/js/pages/maestros/ccostos/create.tsx`/`edit.tsx`: formulario plano con `codigo`, `nombre`, `<Select>` de `cfinanciero_id`, `cod_edificio`, `activo`.
- [x] 3.5 Crear `resources/js/pages/maestros/ccostos/show.tsx`: detalle con acciones editar/eliminar.
- [x] 3.6 Reemplazar `cfinanciero-actions-menu.tsx`/`ccosto-actions-menu.tsx`: de placeholder a acciones reales (ver/editar/eliminar con diálogo de confirmación), calcando `ItemActionsMenu`. Actualizar los `index.tsx` respectivos para pasarles la fila y agregar el botón "Nuevo centro financiero"/"Nuevo centro de costo".

## 4. Tests

- [x] 4.1 `tests/Feature/Maestros/StoreCfinancieroTest.php`, `UpdateCfinancieroTest.php`, `ShowCfinancieroTest.php`, `DestroyCfinancieroTest.php`: alta/edición/detalle/eliminación, rechazo por código duplicado, rechazo sin permiso, bloqueo de eliminación con `ccostos` asociados.
- [x] 4.2 `tests/Feature/Maestros/StoreCcostoTest.php`, `UpdateCcostoTest.php`, `ShowCcostoTest.php`, `DestroyCcostoTest.php`: mismos casos para `Ccosto`, más el bloqueo de eliminación con `clientes_medidores`/`procesos_adquisicion` asociados.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 5.2 `npm run types:check` y `npm run lint:check` sobre el frontend.
- [x] 5.3 `php artisan test --compact --filter=Cfinanciero` y `--filter=Ccosto` para los tests nuevos. 31/31 passed (tras `npm run build` para refrescar el manifest de Vite).
- [x] 5.4 Verificar en el navegador: crear, ver, editar y eliminar un centro financiero y un centro de costo desde Administración. Verificado end-to-end contra `npm run build` con datos reales (6 centros financieros existentes): creación con `<Select>`, detalle, edición precargada y eliminación (hard delete confirmado en BD) para centro financiero; creación confirmada para centro de costo — sin errores de consola ni de servidor. Datos de prueba limpiados después.
- [x] 5.5 `composer test` completo antes de cerrar el change. Pint ✓, PHPStan 0 errores ✓, Pest 364/368 passed (4 skipped, preexistentes).
