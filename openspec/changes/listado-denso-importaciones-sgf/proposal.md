## Why

La vista "Importaciones SGF" (`resources/js/pages/sgf/importaciones/index.tsx`) es una tabla simple que no cumple el requirement "Listados tabulares densos" ya vigente en `openspec/specs/tema-visual-layout/spec.md`, aplicable a cualquier listado tabular de la aplicación. Le faltan columnas de ancho fijo, identidad visual junto al campo principal, badge de estado con tokens semánticos, columnas secundarias truncadas con tooltip y ocultamiento progresivo, búsqueda, y menú de acciones desplegable. Corregirla alinea la única vista de listado que hoy queda fuera de ese patrón ya validado en `resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/index.tsx`.

## What Changes

- Reescribir `sgf/importaciones/index.tsx` siguiendo el patrón de listado denso: tabla `table-fixed` con columnas de ancho fijo, avatar+iniciales junto a "Iniciado por" (fallback "Sistema"), badge de estado con tokens semánticos (`completado`→success, `error`→danger, `en_progreso`→neutro/ámbar), columna "Tipo" y RUT/columnas secundarias truncadas con tooltip y ocultas progresivamente en viewports angostos, fallback `"—"` en `finalizado_en`/`iniciado_por` nulos, menú de acciones desplegable (`Ver detalle`) en vez de fila completa clicable únicamente.
- Agregar campo de búsqueda con debounce 300ms sobre el listado (por tipo y/o usuario que inició la corrida), replicando el patrón `router.get(..., { preserveState, preserveScroll })` de `ordenes-compra-mercado-publico/index.tsx`.
- Ajustar `ImportacionSgfController::index` para aceptar un parámetro de búsqueda (`q`) y filtrar `trabajos_integracion` por tipo o por el nombre del usuario que inició la corrida, devolviendo también `q` como prop a la vista.
- El título de página y controles del encabezado usan la escala tipográfica reducida del tema (ya heredada automáticamente).
- No cambia: `show.tsx` (detalle de una corrida), el botón "Importar pendientes de SGF" y su flujo, las rutas existentes, `ImportacionSgfResource`, ni ningún test de negocio del flujo de importación.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `consulta-importaciones-sgf`: se agrega el requirement de que el listado de corridas de importación SGF sea filtrable por un término de búsqueda (tipo o usuario que inició la corrida), y que se presente siguiendo el patrón de listado denso ya definido en `tema-visual-layout`.

## Impact

- `resources/js/pages/sgf/importaciones/index.tsx` (reescritura visual y de datos).
- `app/Http/Controllers/Sgf/ImportacionSgfController.php` (filtro de búsqueda en `index`).
- Posiblemente un nuevo componente de badge de estado para `TrabajoIntegracion` (o reutilización de uno existente si aplica), análogo a `OrdenCompraEstadoBadge`.
- Tests: actualizar/agregar cobertura en `tests/Feature/Sgf/ConsultarImportacionesSgfTest.php` para el filtro de búsqueda.
