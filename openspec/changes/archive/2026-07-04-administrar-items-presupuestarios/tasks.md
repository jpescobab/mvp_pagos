## 1. Backend: autorización

- [x] 1.1 Crear `app/Policies/ItemPolicy.php` con `view`, `create`, `update`, `delete`, todos chequeando `$user->can('core_institucional.administrar')` (calcar `ProveedorPolicy`).
- [x] 1.2 Registrar `ItemPolicy` para el modelo `Item` (verificar si el proyecto usa auto-discovery de Policies o requiere registro explícito en un provider, siguiendo cómo está registrada `ProveedorPolicy`). Confirmado: se registra explícitamente en `AppServiceProvider::configureAuthorization()`, igual que `ProveedorPolicy`.
- [x] 1.3 Crear `app/Http/Requests/Maestros/StoreItemRequest.php`: `authorize()` chequea `core_institucional.administrar`; `rules()`: `codigo` (`required`, `string`, `max:255`, `unique:items,codigo`), `nombre` (`required`, `string`, `max:255`), `descripcion` (`nullable`, `string`), `activo` (`boolean`).
- [x] 1.4 Crear `app/Http/Requests/Maestros/UpdateItemRequest.php`: igual que Store pero `unique:items,codigo` ignorando el propio `item` en edición (`Rule::unique('items', 'codigo')->ignore($this->item)`).

## 2. Backend: controlador, resource y rutas

- [x] 2.1 Crear `app/Http/Resources/Maestros/ItemResource.php` exponiendo `id`, `codigo`, `nombre`, `descripcion`, `activo`.
- [x] 2.2 Crear `app/Http/Controllers/Maestros/ItemController.php` con `index` (búsqueda por `codigo`/`nombre`, `orderBy('codigo')`, `paginate(20)->withQueryString()`), `create`, `store`, `show`, `edit`, `update`, `destroy` — calcar la estructura de `ProveedorController` (sin el manejo de archivo `documento_respaldo`, que no aplica aquí).
- [x] 2.3 En `destroy`, verificar `$item->asignaciones()->exists() || $item->catalogos()->exists()` antes de eliminar; si hay relaciones, flash de error y `back()` sin eliminar (mismo patrón que `relacionQueImpideEliminar` de `ProveedorController`).
- [x] 2.4 Agregar las 7 rutas (`index/create/store/show/edit/update/destroy`) en `routes/maestros.php`, dentro del grupo `maestros` ya existente, con prefijo `items` y nombres `maestros.items.*`.

## 3. Frontend: páginas

- [x] 3.1 Crear `resources/js/pages/maestros/items/index.tsx` siguiendo el patrón de "Listados tabulares densos" (ver `resources/js/pages/maestros/cfinancieros/index.tsx` como referencia): columnas `codigo`, `nombre`, `descripcion` (truncada con tooltip), badge de estado `activo`/`inactivo`, búsqueda con debounce 300ms, paginación simple, menú de acciones (ver/editar/eliminar).
- [x] 3.2 Crear `resources/js/pages/maestros/items/create.tsx`: formulario plano (no wizard) con `codigo`, `nombre`, `descripcion`, `activo` (switch/checkbox).
- [x] 3.3 Crear `resources/js/pages/maestros/items/edit.tsx`: mismo formulario que create, precargado.
- [x] 3.4 Crear `resources/js/pages/maestros/items/show.tsx`: vista de solo lectura de los 4 campos, con acciones editar/eliminar.
- [x] 3.5 Regenerar los helpers de Wayfinder (`php artisan wayfinder:generate --with-form`) para que `resources/js/routes/maestros/items` y `resources/js/actions/...` existan.

## 4. Frontend: sidebar

- [x] 4.1 En `resources/js/components/app-sidebar.tsx`, agregar `import { index as items } from '@/routes/maestros/items'` (import con nombre, no el export por defecto) y un ítem nuevo "Ítems Presupuestarios" (icono `Tags` de `lucide-react`) dentro de `administracionNavItems`, junto a Proveedores y Clientes Medidores.

## 5. Tests

- [x] 5.1 `tests/Feature/Maestros/ConsultarCatalogoItemsTest.php`: listado, búsqueda por código/nombre, paginación.
- [x] 5.2 `tests/Feature/Maestros/StoreItemTest.php`: alta válida, rechazo por código duplicado, rechazo sin permiso.
- [x] 5.3 `tests/Feature/Maestros/ShowItemTest.php`: ver un ítem existente.
- [x] 5.4 `tests/Feature/Maestros/UpdateItemTest.php`: edición válida, rechazo por código duplicado con otro ítem, rechazo sin permiso.
- [x] 5.5 `tests/Feature/Maestros/DestroyItemTest.php`: eliminación de un ítem sin relaciones; bloqueo de eliminación si tiene asignaciones o catálogos asociados (crear una `Asignacion`/`Catalogo` de prueba ligada al ítem para el caso bloqueado).

## 6. Validación

- [x] 6.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 6.2 `npm run types:check` y `npm run lint:check` sobre el frontend.
- [x] 6.3 `php artisan test --compact --filter=Item` para los tests nuevos. 34/34 passed.
- [x] 6.4 Verificar en el navegador: listar, crear, editar, ver y eliminar un ítem desde el sidebar de Administración. Verificado end-to-end contra `npm run build` (el dev server de Vite tuvo un problema de red IPv6 no relacionado con este cambio): listado con datos reales (12 ítems ya sembrados), creación, vista de detalle con toast, edición y eliminación (soft delete confirmado en BD) — todo sin errores de consola ni de servidor.
- [x] 6.5 `composer test` completo antes de cerrar el change. Pint ✓, PHPStan 0 errores ✓, Pest 313/317 passed (4 skipped, preexistentes).
