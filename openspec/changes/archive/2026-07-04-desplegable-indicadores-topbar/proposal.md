## Why

Los indicadores económicos (UF, UTM, dólar, IPC) hoy solo son visibles en el panel general y en el catálogo de consulta paginado. Finanzas los necesita a la vista desde cualquier página, sin tener que navegar al panel o al listado completo.

## What Changes

- Se agrega un desplegable en el topbar (junto al selector de tema), con un ícono de indicadores económicos, que al abrirse muestra el último valor registrado de UF, UTM, dólar e IPC.
- Se comparte globalmente (vía `HandleInertiaRequests`) el último valor de esos 4 indicadores, reutilizando `IndicadorEconomicoSelector::ultimosPorTipo()` ya existente — sin nuevas rutas ni controladores.
- Se extrae a un módulo compartido (`resources/js/lib/indicadores.ts`) el formateo de valores e etiquetas de indicador que hoy vive duplicado dentro de `dashboard.tsx`, para que el nuevo desplegable del topbar lo reutilice sin repetir la lógica.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `tema-visual-layout`: se agrega un requirement nuevo para el desplegable de indicadores económicos en el topbar (no cambia el requirement existente de tema/menú de usuario).

## Impact

- **Backend**: `App\Http\Middleware\HandleInertiaRequests` (nueva prop compartida `indicadoresTopbar`).
- **Frontend**: `resources/js/lib/indicadores.ts` (nuevo, con lo extraído de `dashboard.tsx`), `resources/js/components/topbar-indicadores.tsx` (nuevo), `resources/js/components/app-sidebar-header.tsx` (inserta el nuevo desplegable), `resources/js/pages/dashboard.tsx` (usa el módulo compartido en vez de su copia local).
