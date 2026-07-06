## 1. Backend: autorización

- [x] 1.1 Crear `app/Policies/AsignacionPolicy.php` y `app/Policies/CatalogoPolicy.php` con `create`/`update`/`delete` chequeando `core_institucional.administrar` (calcar `ItemPolicy`).
- [x] 1.2 Registrar ambas policies en `AppServiceProvider::configureAuthorization()`.
- [x] 1.3 Crear `app/Http/Requests/Maestros/StoreAsignacionRequest.php` y `StoreCatalogoRequest.php`: `authorize()` chequea `core_institucional.administrar`; `rules()`: `codigo` (`required`, `string`, `max:255`, `unique:asignaciones,codigo` / `unique:catalogos,codigo`), `nombre` (`required`), `descripcion` (`nullable`), `activo` (`boolean`).
- [x] 1.4 Crear `app/Http/Requests/Maestros/UpdateAsignacionRequest.php` y `UpdateCatalogoRequest.php`: igual que Store pero con `unique` ignorando el propio registro.

## 2. Backend: controladores, resources y rutas

- [x] 2.1 Crear `app/Http/Resources/Maestros/AsignacionResource.php` y `CatalogoResource.php` exponiendo `id`, `codigo`, `nombre`, `descripcion`, `activo`.
- [x] 2.2 Crear `app/Http/Controllers/Maestros/AsignacionController.php` con `store(Item $item, StoreAsignacionRequest $request)`, `update(Item $item, Asignacion $asignacion, UpdateAsignacionRequest $request)`, `destroy(Item $item, Asignacion $asignacion)` — sin `index`/`create`/`edit`/`show` propios (calcar `FacturaController` en estructura, no en dominio).
- [x] 2.3 Crear `app/Http/Controllers/Maestros/CatalogoController.php` con la misma forma que `AsignacionController`, para `Catalogo`.
- [x] 2.4 Agregar las rutas anidadas en `routes/maestros.php`: `POST items/{item}/asignaciones`, `PATCH items/{item}/asignaciones/{asignacion}`, `DELETE items/{item}/asignaciones/{asignacion}` y las 3 equivalentes para `catalogos`, con nombres `maestros.items.asignaciones.*` / `maestros.items.catalogos.*`.
- [x] 2.5 En `ItemController::index`, dejar sin cambios (no cargar `asignaciones`/`catalogos` ahí). En `ItemController::show`, cambiar a `Item::with(['asignaciones', 'catalogos'])->findOrFail(...)` o equivalente para que la relación llegue cargada. (Usé `$item->load([...])` sobre el modelo ya resuelto por route model binding — equivalente, sin query extra de más.)
- [x] 2.6 En `ItemResource`, agregar `'asignaciones' => AsignacionResource::collection($this->whenLoaded('asignaciones'))` y el equivalente para `catalogos`.

## 3. Frontend

- [x] 3.1 Agregar tipos `Asignacion` y `Catalogo` en `resources/js/types/maestros.ts` (mismos campos que `ItemPresupuestario` menos que sin `item_id` explícito en el tipo, ya que viaja anidado dentro de `ItemPresupuestario.asignaciones`/`.catalogos`), y extender `ItemPresupuestario` con `asignaciones?: Asignacion[]` y `catalogos?: Catalogo[]`.
- [x] 3.2 Regenerar los helpers de Wayfinder (`php artisan wayfinder:generate --with-form`) para las rutas nuevas.
- [x] 3.3 En `resources/js/pages/maestros/items/show.tsx`, agregar sección "Asignaciones": lista (`divide-y`) con nombre/código/estado, botón editar/eliminar por fila (editar = inputs inline reemplazando la fila, calcando el patrón visual ya usado en el resto de Maestros), formulario inline de alta al final de la sección — mismo layout que la sección "Facturas" de `casos/show.tsx`. Implementado como componente compartido `ClasificadorHijoSeccion` (Asignacion y Catalogo son estructuralmente idénticos) en vez de duplicar el código dos veces.
- [x] 3.4 Repetir 3.3 para la sección "Catálogos". Reutiliza el mismo componente `ClasificadorHijoSeccion`.

## 4. Tests

- [x] 4.1 `tests/Feature/Maestros/StoreAsignacionTest.php`: alta válida bajo un ítem, rechazo por código duplicado, rechazo sin permiso.
- [x] 4.2 `tests/Feature/Maestros/UpdateAsignacionTest.php`: edición válida, rechazo por código duplicado con otra asignación, rechazo sin permiso.
- [x] 4.3 `tests/Feature/Maestros/DestroyAsignacionTest.php`: eliminación (soft delete), rechazo sin permiso.
- [x] 4.4 `tests/Feature/Maestros/StoreCatalogoTest.php`, `UpdateCatalogoTest.php`, `DestroyCatalogoTest.php`: mismos casos que Asignación, para Catálogo.
- [x] 4.5 Test de que `maestros.items.show` incluye `asignaciones`/`catalogos` del ítem en la respuesta Inertia.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 5.2 `npm run types:check` y `npm run lint:check` sobre el frontend.
- [x] 5.3 `php artisan test --compact --filter=Asignacion` y `--filter=Catalogo` para los tests nuevos. 43/43 passed.
- [x] 5.4 Verificar en el navegador: crear, editar y eliminar una asignación y un catálogo desde el detalle de un ítem. Verificado end-to-end contra `npm run build` sobre un ítem real con datos existentes (Textiles, Vestuario y Calzado): creación, edición inline y eliminación (soft delete confirmado en BD) para asignación; creación confirmada para catálogo — sin errores de consola ni de servidor. Datos de prueba limpiados después.
- [x] 5.5 `composer test` completo antes de cerrar el change. Pint ✓, PHPStan 0 errores ✓, Pest 330/334 passed (4 skipped, preexistentes).
