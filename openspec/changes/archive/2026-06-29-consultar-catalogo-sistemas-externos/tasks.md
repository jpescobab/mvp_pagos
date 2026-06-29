## 1. Backend

- [x] 1.1 Crear `App\Http\Controllers\Integraciones\SistemaExternoController::index()`: `SistemaExterno::withCount('trabajosIntegracion')->orderBy('codigo')->get()`, renderiza `integraciones/sistemas-externos/index`.
- [x] 1.2 Crear `App\Http\Resources\Integraciones\SistemaExternoResource` con `id`, `codigo`, `nombre`, `tipo_integracion`, `activo`, `trabajos_integracion_count` (`whenCounted`).
- [x] 1.3 Crear `routes/integraciones.php` con `GET integraciones/sistemas-externos` (`integraciones.sistemas-externos.index`), middleware `auth`, y requerirlo desde `routes/web.php`.

## 2. Frontend

- [x] 2.1 Agregar tipo `SistemaExterno` en `resources/js/types/integraciones.ts`.
- [x] 2.2 Crear página `resources/js/pages/integraciones/sistemas-externos/index.tsx`, tabla simple sin paginación, mismo estilo que `workflow/definiciones/index.tsx`.
- [x] 2.3 Agregar ítem de navegación "Sistemas Externos" en `resources/js/components/app-sidebar.tsx`.

## 3. Tests

- [x] 3.1 Feature test: el catálogo lista los sistemas externos sembrados con su código, tipo de integración y estado activo.

## 4. Validación

- [x] 4.1 Ejecutar `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check` y `php artisan test --filter=SistemaExterno --compact`.
