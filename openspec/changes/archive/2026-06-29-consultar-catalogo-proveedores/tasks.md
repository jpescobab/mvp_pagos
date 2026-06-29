## 1. Backend

- [x] 1.1 Crear `App\Http\Resources\Maestros\ProveedorResource`: `id`, `rutproveedor`, `nombre`, `correo`, `direccion`, `contacto`, `activo`.
- [x] 1.2 Crear `App\Http\Controllers\Maestros\ProveedorController::index()`: filtra por `rutproveedor`/`nombre` (`LIKE`) cuando llega `q`, ordena por `nombre`, pagina 20.
- [x] 1.3 Crear `routes/maestros.php` con `GET maestros/proveedores`, bajo middleware `auth`.
- [x] 1.4 Agregar `require __DIR__.'/maestros.php';` en `routes/web.php`.

## 2. Frontend

- [x] 2.1 Crear `resources/js/types/maestros.ts`: tipo `Proveedor`.
- [x] 2.2 Crear `resources/js/pages/maestros/proveedores/index.tsx`: input de búsqueda (debounced, `router.get` con `preserveState`) + tabla paginada con RUT, nombre, correo, dirección, contacto, activo.
- [x] 2.3 Agregar ítem "Proveedores" en `resources/js/components/app-sidebar.tsx`.

## 3. Tests y spec

- [x] 3.1 Test Feature: el listado sin filtro devuelve proveedores paginados.
- [x] 3.2 Test Feature: buscar por RUT o por nombre devuelve solo las coincidencias.
- [x] 3.3 Test Feature: un usuario no autenticado es redirigido al login.
- [x] 3.4 `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check`, `php artisan test --compact`.
