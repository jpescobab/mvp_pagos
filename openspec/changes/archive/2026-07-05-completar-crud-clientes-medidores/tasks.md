## 1. Backend: autorización

- [x] 1.1 Crear `app/Policies/ClienteMedidorPolicy.php` con `view`/`create`/`update`/`delete` chequeando `core_institucional.administrar` (calcar `ItemPolicy`).
- [x] 1.2 Registrar la policy en `AppServiceProvider::configureAuthorization()`.
- [x] 1.3 Crear `app/Http/Requests/Maestros/StoreClienteMedidorRequest.php`: `authorize()` chequea `core_institucional.administrar`; `rules()`: `numero_cliente` (`required`, `string`, `max:255`, `unique:clientes_medidores,numero_cliente`), `ccosto_id` (`required`, `exists:ccostos,id`), `proveedor_id` (`nullable`, `exists:proveedores,id`), `tipo_suministro` (`required`, `string`, `max:255`), `direccion_suministro` (`nullable`, `string`), `activo` (`boolean`).
- [x] 1.4 Crear `app/Http/Requests/Maestros/UpdateClienteMedidorRequest.php`: igual que Store pero `unique` de `numero_cliente` ignorando el propio registro. (Route param `clienteMedidor`, camelCase, siguiendo la convención ya usada para otros bindings de dos palabras como `egresoCgu`.)

## 2. Backend: controlador y rutas

- [x] 2.1 Agregar a `ClienteMedidorController`: `create()` (carga `Ccosto::all()` y `Proveedor::all()` mapeados a `{id, codigo, nombre}` / `{id, nombre, rutproveedor}`, calcando `ProcesoAdquisicionController::create()`), `store()`, `show()`, `edit()` (misma carga de catálogos que `create()`), `update()`, `destroy()` (sin verificación de relaciones dependientes — nada referencia `ClienteMedidor` hoy).
- [x] 2.2 Agregar las rutas `crear`/`store`/`show`/`edit`/`update`/`destroy` en `routes/maestros.php` bajo el prefijo `clientes-medidores` ya existente, con nombres `maestros.clientes-medidores.*`.

## 3. Frontend

- [x] 3.1 Agregar tipos `CcostoSeleccionable`/`ProveedorSeleccionable` (o reutilizar si ya existen en `types/adquisiciones.ts`, en cuyo caso importarlos desde ahí) en `resources/js/types/maestros.ts`. Ya existen en `types/adquisiciones.ts` con el shape correcto — se reutilizan por import en vez de duplicar. Además: `ClienteMedidorResource`/tipo `ClienteMedidor` necesitaban exponer el `id` de `proveedor`/`ccosto` (no solo nombre/código) para poder preseleccionar el `<Select>` en el formulario de edición — ajuste no listado explícitamente en el plan pero necesario para 3.4.
- [x] 3.2 Regenerar los helpers de Wayfinder (`php artisan wayfinder:generate --with-form`).
- [x] 3.3 Crear `resources/js/pages/maestros/clientes-medidores/create.tsx`: formulario plano con `numero_cliente`, `<Select>` de `ccosto_id` (requerido), `<Select>` de `proveedor_id` (opcional, sentinela `SIN_PROVEEDOR` calcando `procesos/crear.tsx`), `tipo_suministro`, `direccion_suministro`, `activo`.
- [x] 3.4 Crear `resources/js/pages/maestros/clientes-medidores/edit.tsx`: mismo formulario que create, precargado.
- [x] 3.5 Crear `resources/js/pages/maestros/clientes-medidores/show.tsx`: vista de solo lectura con acciones editar/eliminar. Reutiliza `ClienteMedidorStatusBadge` ya existente.
- [x] 3.6 Reemplazar `resources/js/components/maestros/cliente-medidor-actions-menu.tsx`: de placeholder "Disponible próximamente" a acciones reales (ver/editar/eliminar con diálogo de confirmación), calcando `ProveedorActionsMenu`. También: actualicé `index.tsx` para pasarle `clienteMedidor={cliente}` al menú (antes se llamaba sin props) y agregué el botón "Nuevo cliente medidor" que faltaba (index.tsx no tenía forma de llegar al alta), calcando el header de `proveedores/index.tsx`.

## 4. Tests

- [x] 4.1 `tests/Feature/Maestros/StoreClienteMedidorTest.php`: alta válida, rechazo por `numero_cliente` duplicado, rechazo sin permiso, rechazo si `ccosto_id` no existe.
- [x] 4.2 `tests/Feature/Maestros/ShowClienteMedidorTest.php`: ver un cliente medidor existente.
- [x] 4.3 `tests/Feature/Maestros/UpdateClienteMedidorTest.php`: edición válida, rechazo por `numero_cliente` duplicado con otro, rechazo sin permiso.
- [x] 4.4 `tests/Feature/Maestros/DestroyClienteMedidorTest.php`: eliminación (soft delete), rechazo sin permiso.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 5.2 `npm run types:check` y `npm run lint:check` sobre el frontend.
- [x] 5.3 `php artisan test --compact --filter=ClienteMedidor` para los tests nuevos. 11/11 passed (tras `npm run build` para refrescar el manifest de Vite, requerido por `ShowClienteMedidorTest` al renderizar la vista completa).
- [x] 5.4 Verificar en el navegador: crear, ver, editar y eliminar un cliente medidor desde Administración. Verificado end-to-end contra `npm run build` sobre datos reales (39 clientes existentes): listado con botón "Nuevo", creación con los dos `<Select>` (ccosto/proveedor), vista de detalle, edición precargada correctamente, y eliminación (soft delete confirmado en BD) — sin errores de consola ni de servidor. Datos de prueba limpiados después.
- [x] 5.5 `composer test` completo antes de cerrar el change. Pint ✓, PHPStan 0 errores ✓, Pest 341/345 passed (4 skipped, preexistentes).
