## Context

`IndicadorEconomicoSelector::ultimosPorTipo()` resuelve, para una lista de tipos, el último valor registrado de cada uno (usado para el desplegable del topbar y las tarjetas del panel general). Hoy:

- `HandleInertiaRequests::share()` la llama en cada request autenticado con `['UF', 'UTM', 'USD', 'IPC']`.
- `DashboardController::index()` la vuelve a llamar con `['UF', 'UTM', 'UTA', 'IPC', 'USD']` en cada visita al dashboard.
- La implementación actual resuelve cada tipo con un `->first()` dentro de un `foreach`, es decir, N consultas independientes por llamada.

Los valores subyacentes solo cambian cuando corre `IndicadorEconomicoImporter` (1 vez al día para USD, 1 vez al mes para UF/UTM/UTA/IPC), así que el mismo dato se recalcula desde la base de datos en cada carga de página sin necesidad.

## Goals / Non-Goals

**Goals:**
- Servir `ultimosPorTipo()` desde caché, con TTL corto, para que la mayoría de los requests no toquen `indicadores_economicos`.
- Invalidar la caché de un tipo específico apenas se registre un nuevo valor de ese tipo en una importación, para no servir datos vencidos tras un import manual o programado.
- En cache-miss, resolver todos los tipos faltantes con una sola consulta SQL (no N consultas en loop), portable entre PostgreSQL (producción/desarrollo) y SQLite (tests).
- Que `HandleInertiaRequests` y `DashboardController` compartan automáticamente el trabajo cacheado para los tipos que piden en común (UF, UTM, USD, IPC), sin coordinarse explícitamente entre sí.

**Non-Goals:**
- No cambia el algoritmo de fallback de USD (`aplicarFallbackUsd`) ni el comportamiento de `paraFecha()`/`paraPeriodo()`, usados para cálculos puntuales (no para el chip de topbar/dashboard).
- No cambia el backend de caché configurado (`CACHE_STORE` sigue siendo el que defina `.env`); optimizar ese store es una decisión de infraestructura independiente.
- No agrega un Observer genérico de Eloquent sobre `IndicadorEconomico`; la invalidación se dispara puntualmente desde el único método de escritura del importador.

## Decisions

1. **Clave de caché por tipo individual, no por combinación de tipos pedidos.**
   Clave: `indicadores_economicos:ultimo:{tipo}`. Alternativa descartada: cachear por la lista completa de tipos pedida (ej. clave = hash de `['UF','UTM','USD','IPC']`). Se descarta porque el topbar y el dashboard piden conjuntos de tipos distintos (el dashboard además pide `UTA`); con clave por combinación, ambos endpoints seguirían siendo cache-miss entre sí y no se eliminaría la duplicación real que motiva este change. Con clave por tipo, ambos comparten las 4 entradas de UF/UTM/USD/IPC.

2. **TTL corto (5 minutos) además de invalidación activa, no solo uno de los dos.**
   La invalidación activa cubre el flujo normal (importación vía job/artisan). El TTL es una red de seguridad para no dejar un valor "pegado" indefinidamente si en el futuro algún dato se corrige por una vía que no pase por `IndicadorEconomicoImporter` (ej. Tinker en un ambiente de soporte).

3. **Invalidación puntual en `IndicadorEconomicoImporter::crearIndicador()`, no un Observer de Eloquent.**
   `crearIndicador()` ya es el único choke point de escritura del importador (usa `firstOrCreate`). Invalidar ahí evita acoplar un Observer global que dispare ante cualquier `save()` futuro sobre el modelo (por ejemplo, una corrección manual) de forma implícita y difícil de rastrear. Solo se invalida cuando el registro fue realmente creado (`wasRecentlyCreated`), no en cada `firstOrCreate` que solo lee uno existente.

4. **Cache-miss resuelto con una sola consulta ordenada + agrupación en PHP, sin `DISTINCT ON`.**
   Para los tipos faltantes en caché: `IndicadorEconomico::whereIn('tipo', $tiposFaltantes)->orderByDesc('fecha_valor')->orderByDesc('periodo')->get()->groupBy('tipo')->map->first()`. Alternativa descartada: `SELECT DISTINCT ON (tipo) ...`, la forma idiomática en PostgreSQL para "última fila por grupo" — se descarta porque es sintaxis exclusiva de Postgres y la suite de tests corre sobre SQLite en memoria (`phpunit.xml`); usar SQL específico de un driver rompería los tests.

5. **Middleware y controlador comparten el resultado llamando al mismo servicio cacheado, no coordinándose entre sí.**
   `Inertia::render()` no da una forma directa de leer, desde el controlador, props ya compartidas por el middleware. En vez de acoplar ambos, la caché vive dentro de `IndicadorEconomicoSelector::ultimosPorTipo()`: cualquier caller (middleware, controlador, uno futuro) se beneficia automáticamente sin cambios de arquitectura Inertia.

## Risks / Trade-offs

- **[Riesgo]** Un TTL de 5 minutos podría mostrar un valor vencido hasta por ese lapso si la invalidación activa fallara silenciosamente. → **Mitigación**: la invalidación ocurre en el mismo método que persiste el registro (`crearIndicador`), sin condicionales adicionales que puedan saltarse.
- **[Riesgo]** Con `CACHE_STORE=database` (configuración actual), un "cache hit" sigue pagando una consulta a la tabla `cache` en Postgres, más liviana que consultar `indicadores_economicos` con `ORDER BY` pero no gratis. → **Mitigación**: fuera de alcance de este change (ver Non-Goals); queda como mejora de infraestructura independiente.
- **[Riesgo]** La consulta agrupada en PHP trae todas las filas históricas de los tipos en cache-miss, no solo la última fila de cada uno. Con el volumen actual (cientos de filas por tipo por año) es trivial; podría dejar de serlo con varios años de historial. → **Mitigación**: no se optimiza en este change; si el volumen se vuelve un problema medible, evaluar una consulta con `ROW_NUMBER()` condicionada por driver de base de datos.

## Migration Plan

Sin migraciones de base de datos ni pasos de despliegue especiales. Es un cambio de código de servicio + tests; revertir el commit es suficiente si algo sale mal.

## Open Questions

Ninguna bloqueante para implementar.
