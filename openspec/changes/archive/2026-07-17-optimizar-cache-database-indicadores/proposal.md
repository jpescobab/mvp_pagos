## Why

`IndicadorEconomicoSelector::ultimosPorTipo()` ya cachea por código (change archivado `2026-07-04-cache-indicadores-topbar-dashboard`), pero bajo `CACHE_STORE=database` cada `Cache::get()`/`Cache::put()` del loop interno sigue siendo una consulta SQL real contra la tabla `cache` (verificado en `DatabaseStore::get()`/`many()`: `get()` delega a `many()`, y `many()` ejecuta 1 query sin importar cuántas claves se pidan). Como `HandleInertiaRequests::share()` (4 códigos, en cada request Inertia autenticado) y `DashboardController::index()` (5 códigos, solo en `/dashboard`) resuelven cada uno su propia instancia del selector sin compartir lo ya resuelto, cargar `/dashboard` dispara hasta 9 consultas de caché por solo 5 códigos únicos — 4 de ellos ya resueltos milisegundos antes por el middleware en el mismo request. Además, `resolverUltimosPorTipo()` trae todas las filas que matcheen los códigos pedidos y reduce a "la más reciente" en PHP, sin límite en SQL; hoy inofensivo porque `indicadores_economicos` está vacía, pero los jobs programados (`ImportarDolarDiarioJob` diario, `ImportarIndicadoresMensualesJob` mensual) acumularán cientos de filas por año sin purga.

## What Changes

- `IndicadorEconomicoSelector` se registra como singleton en el contenedor y memoiza en memoria de instancia los códigos ya resueltos durante el request, para que una segunda llamada (típicamente `HandleInertiaRequests` + `DashboardController`) no repita ninguna operación de caché para los códigos que se solapan.
- El loop de `Cache::get()`/`Cache::put()` por código se reemplaza por `Cache::many()`/`Cache::putMany()`, reduciendo de N a 1 la cantidad de consultas al store de caché por llamada con códigos faltantes.
- `resolverUltimosPorTipo()` trae, a nivel de SQL (no de PHP), únicamente la fila más reciente de cada código mediante una función de ventana (`ROW_NUMBER()` particionada por código), portable entre PostgreSQL y SQLite.
- Sin cambios de comportamiento observable: los valores, su orden y las reglas de invalidación siguen siendo los mismos.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `indicadores-economicos-cmf-sii`: el requirement "Seleccionar indicador para cálculos" agrega que la resolución de `ultimosPorTipo()` reutiliza, dentro de un mismo request, lo que ya haya resuelto otra llamada sobre la misma instancia, que la consulta al store de caché para varios códigos es una sola operación, y que la consulta a la base de datos para códigos sin caché vigente trae únicamente la fila más reciente de cada código.

## Impact

- `app/Services/Indicadores/IndicadorEconomicoSelector.php`: memoización de instancia, `Cache::many`/`Cache::putMany`, `resolverUltimosPorTipo()` reescrito con función de ventana.
- `app/Providers/AppServiceProvider.php`: binding singleton del selector.
- `app/Http/Middleware/HandleInertiaRequests.php` y `app/Http/Controllers/DashboardController.php`: sin cambios de código, se benefician automáticamente del singleton compartido.
- `app/Services/Indicadores/ServicioPersistenciaIndicadores.php`: sin cambios de código, pasa a compartir instancia (y memo) con el resto del request al invalidar.
- `tests/Feature/Indicadores/IndicadorEconomicoSelectorTest.php`: tests nuevos forzando el store `database` para probar el comportamiento real de producción, que los tests actuales (bajo el store `array` de test) no pueden detectar.
