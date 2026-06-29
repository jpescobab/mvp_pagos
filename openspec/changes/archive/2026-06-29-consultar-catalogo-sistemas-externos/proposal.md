## Why

`integraciones-api-browser-automation` (tarea 09) ya sembró el catálogo de `sistemas_externos` (SGF, CGU, BancoEstado, SII, CMF, Mercado Público) con su código, mecanismo de integración y estado activo, base de cualquier `trabajo_integracion`, `solicitud_api_externa` o conector Playwright. Hoy ningún controlador lo expone: no hay forma de confirmar qué sistemas externos están registrados, cuál es su mecanismo de integración vigente (api/playwright/manual) o si están activos, salvo consultando la base de datos directamente.

## What Changes

- Exponer un listado de solo lectura de `sistemas_externos` con código, nombre, tipo de integración, estado activo y cantidad de trabajos de integración asociados.
- Abierto a cualquier usuario autenticado, sin permiso adicional — mismo nivel de acceso que el catálogo de proveedores y las definiciones de workflow.
- Sin página de detalle separada: el catálogo es pequeño (6 sistemas) y todos sus campos ya son visibles en la fila del listado.

## Capabilities

### New Capabilities
- `consultar-catalogo-sistemas-externos`: listar el catálogo de sistemas externos registrados.

## Impact

- Nuevos: `App\Http\Controllers\Integraciones\SistemaExternoController`, `App\Http\Resources\Integraciones\SistemaExternoResource`, `routes/integraciones.php`, página `resources/js/pages/integraciones/sistemas-externos/index.tsx`.
- Modificados: `routes/web.php` (require del nuevo archivo), `resources/js/components/app-sidebar.tsx` (nuevo ítem de navegación).
- Sin cambios de esquema ni de permisos.
