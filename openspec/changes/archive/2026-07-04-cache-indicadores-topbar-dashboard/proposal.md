## Why

Cada request autenticado (incluidas las navegaciones parciales de Inertia) dispara `IndicadorEconomicoSelector::ultimosPorTipo()` desde `HandleInertiaRequests::share()` para alimentar el desplegable del topbar, y el `DashboardController` vuelve a llamar el mismo método con otro set de tipos para las tarjetas del panel general. `ultimosPorTipo()` además resuelve cada tipo con una consulta independiente dentro de un `foreach`, así que solo cargar el dashboard dispara hasta 9 consultas a `indicadores_economicos` por los mismos valores, que en la práctica solo cambian 1 vez al día (UF, USD) o 1 vez al mes (UTM, UTA, IPC). Esto agrega latencia a cada carga de página sin ningún beneficio, porque el dato es el mismo para todos los usuarios durante horas.

## What Changes

- Cachear el resultado de `ultimosPorTipo()` (una entrada de caché por tipo de indicador) con un TTL corto, para que solo la primera consulta tras la expiración golpee la base de datos.
- Invalidar explícitamente esas entradas de caché cuando `IndicadorEconomicoImporter` registre una nueva importación, para no servir un valor vencido después de una importación manual o del job programado.
- Sustituir el `foreach` con N consultas de `ultimosPorTipo()` por una sola consulta que traiga el último valor de todos los tipos solicitados a la vez, preservando intacta la lógica de fallback de USD (`aplicarFallbackUsd`).
- Hacer que `HandleInertiaRequests` y `DashboardController` reutilicen la misma capa cacheada en vez de golpear la base de datos cada uno por su lado con distintos subconjuntos de tipos.
- Sin cambios de comportamiento observable: los valores mostrados en el topbar y en el panel general deben seguir siendo siempre los últimos vigentes.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `indicadores-economicos-cmf-sii`: el requirement "Seleccionar indicador para cálculos" agrega el comportamiento de caché con invalidación en la importación, para que quede especificado que la selección de "últimos por tipo" puede servirse desde caché pero nunca puede quedar desactualizada tras una nueva importación.

## Impact

- `app/Http/Middleware/HandleInertiaRequests.php`: usa la selección cacheada en vez de llamar directo al selector.
- `app/Http/Controllers/DashboardController.php`: reutiliza la misma selección cacheada, sin duplicar la consulta.
- `app/Services/Indicadores/IndicadorEconomicoSelector.php`: `ultimosPorTipo()` pasa de N queries en loop a una sola consulta agrupada, y queda envuelto en caché con invalidación.
- `app/Services/Indicadores/IndicadorEconomicoImporter.php`: al finalizar una importación, invalida las entradas de caché de los tipos importados.
- Tests existentes de indicadores económicos (selector, middleware, dashboard) para cubrir el nuevo comportamiento de caché e invalidación.
