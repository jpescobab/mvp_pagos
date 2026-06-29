## 1. Backend

- [x] 1.1 Crear `App\Http\Controllers\Maestros\ClienteMedidorController::index()`: `ClienteMedidor::with(['proveedor', 'ccosto'])->orderBy('numero_cliente')->get()`, renderiza `maestros/clientes-medidores/index`.
- [x] 1.2 Crear `App\Http\Resources\Maestros\ClienteMedidorResource` con `id`, `numero_cliente`, `proveedor` (nombre/rutproveedor), `ccosto` (codigo/nombre), `tipo_suministro`, `direccion_suministro`, `activo`.
- [x] 1.3 Agregar `GET maestros/clientes-medidores` (`maestros.clientes-medidores.index`) en `routes/maestros.php`.

## 2. Frontend

- [x] 2.1 Agregar tipo `ClienteMedidor` en `resources/js/types/maestros.ts`.
- [x] 2.2 Crear página `resources/js/pages/maestros/clientes-medidores/index.tsx`, tabla simple sin paginación, mismo estilo que `maestros/proveedores/index.tsx`.
- [x] 2.3 Agregar ítem de navegación "Clientes Medidores" en `resources/js/components/app-sidebar.tsx`.

## 3. Tests

- [x] 3.1 Feature test: el catálogo lista los clientes medidores sembrados con su proveedor y centro de costo.

## 4. Validación

- [x] 4.1 Ejecutar `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check` y `php artisan test`. Verificado en navegador real: los 39 clientes medidores sembrados se listan correctamente con proveedor y centro de costo.
