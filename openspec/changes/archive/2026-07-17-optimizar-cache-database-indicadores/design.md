## Context

`CACHE_STORE=database` (`.env`) usa la misma conexión pgsql que el resto de la app. Verificado en `vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php`: `get($key)` (línea 115) delega a `many([$key])` (línea 127), y `many()` ejecuta siempre 1 consulta SQL contra la tabla `cache`, sin importar cuántas claves se pidan; mismo patrón en `put()`/`putMany()` (líneas 177-199, `putMany()` hace un solo `upsert()`). Esto significa que, bajo este store, un loop de N `Cache::get()`/`Cache::put()` cuesta N queries, mientras que `Cache::many()`/`Cache::putMany()` con las mismas N claves cuesta 1.

`IndicadorEconomicoSelector::ultimosPorTipo()` hoy hace ese loop. Dos callers lo invocan en cada carga de `/dashboard`: `HandleInertiaRequests::share()` (4 códigos, en cada request Inertia autenticado) y `DashboardController::index()` (5 códigos, solo dashboard), cada uno resolviendo su propia instancia del selector desde el contenedor (`AppServiceProvider::register()` está vacío, sin binding). Resultado: hasta 9 consultas de caché por solo 5 códigos únicos en una sola carga de página.

`resolverUltimosPorTipo()` (la ruta de cache-miss) hace `whereIn('codigo', $codigos)->get()->groupBy('codigo')`, sin límite — trae todas las filas que matcheen y reduce a "la más reciente" en PHP. La tabla `indicadores_economicos` está vacía hoy (verificado por query directa), así que esto no afecta el comportamiento actual, pero `ImportarDolarDiarioJob` (diario, UF/USD) e `ImportarIndicadoresMensualesJob` (mensual, UTM/UTA/IPC) acumularán filas sin purga.

`phpunit.xml` fuerza `CACHE_STORE=array` en tests — bajo ese store, `Cache::get()`/`Cache::put()` son operaciones de memoria pura, sin costo. Los tests actuales de `IndicadorEconomicoSelectorTest.php` (incluido el que cuenta queries con `DB::enableQueryLog()`) pasan hoy sin poder detectar ninguno de los problemas de arriba, porque en `array` store no hay nada que detectar.

## Goals / Non-Goals

**Goals:**
- Reducir a 1 sola consulta al store de caché por llamada con códigos faltantes (`Cache::many`/`Cache::putMany`).
- Eliminar la repetición de trabajo entre `HandleInertiaRequests` y `DashboardController` dentro del mismo request, para los códigos que ambos piden.
- Acotar `resolverUltimosPorTipo()` a "una fila por código" en SQL, no en PHP, de forma portable entre PostgreSQL y SQLite.
- Dejar un test que reproduzca el comportamiento real bajo `CACHE_STORE=database`, no solo bajo el `array` de test.

**Non-Goals:**
- No cambiar `CACHE_STORE`/`SESSION_DRIVER` ni ninguna variable de `.env` — decisión de infraestructura ya descartada en la auditoría previa (no hay redis/predis instalado).
- No agregar índices nuevos a `indicadores_economicos` — la tabla está vacía hoy, no hay `EXPLAIN` que lo justifique todavía.
- No tocar `paraFecha()`, `paraPeriodo()` ni `aplicarFallbackUsd()`.
- No modificar `HandleInertiaRequests` ni `DashboardController` — se benefician automáticamente del singleton sin cambiar su código.
- No incluye el change relacionado de caché de permisos compartidos (dominio `seguridad-auditoria`) — se propone por separado.

## Decisions

**1. Memoización de instancia + `IndicadorEconomicoSelector` como singleton en el contenedor.**
Alternativa considerada: coordinar `HandleInertiaRequests` y `DashboardController` para que uno pase sus resultados al otro (por ejemplo, vía un atributo del `Request`). Descartada porque acopla dos capas que hoy no se conocen entre sí (middleware global vs. controller de una ruta específica) y no generaliza a futuros callers. Un singleton con memo de instancia resuelve el problema para cualquier caller presente o futuro sin acoplarlos.
El memo usa `array_key_exists()`, no `isset()`, porque un código sin ningún indicador se cachea legítimamente como `null` (comportamiento actual) — `isset()` trataría ese `null` como "no resuelto todavía" y volvería a consultar en cada llamada dentro del mismo request.
Sin Octane instalado (verificado en `composer.json`), el singleton dura exactamente un request HTTP en producción — no hay riesgo de fuga de estado entre usuarios/requests distintos.
`invalidarUltimoPorTipo()` limpia memo de instancia + entrada de caché en el mismo método. Esto es necesario porque el contenedor de un test de Pest no se reconstruye entre dos llamadas a `$this->get()` dentro del mismo test — `tests/Feature/DashboardTest.php` (test "el topbar y el dashboard reflejan el indicador recién importado sin esperar el TTL de la caché") hace exactamente eso: dos requests simulados al dashboard con una importación real en medio, y espera que el segundo refleje el valor nuevo. Si solo se limpiara el memo sin limpiar la caché (o viceversa), ese test fallaría de forma intermitente según cuál capa consulte primero.

**2. `Cache::many()`/`Cache::putMany()` en vez del loop `Cache::get()`/`Cache::put()`.**
`Cache::many()` acepta un array asociativo `[clave => default]`, igual que `get($key, $default)` — permite preservar el sentinel `CACHE_MISS` actual sin perder la distinción "nunca cacheado" vs. "cacheado como `null`". Verificado que esto reduce N queries a 1 bajo `database` store sin cambiar el contrato de la función.

**3. `resolverUltimosPorTipo()` con función de ventana (`ROW_NUMBER()`), no `DISTINCT ON`.**
`DISTINCT ON` es sintaxis exclusiva de PostgreSQL; los tests corren contra SQLite (`phpunit.xml`, `DB_CONNECTION=sqlite`, `:memory:`). `ROW_NUMBER() OVER (PARTITION BY codigo ORDER BY fecha_valor DESC, periodo DESC)` es SQL estándar soportado por ambos motores (SQLite 3.25+, incluido en el PHP del entorno). Se implementa como subquery vía `fromSub()`, filtrando `rn = 1`, preservando el shape de retorno actual y los casts de Eloquent.

## Risks / Trade-offs

- **Usar `isset()` en vez de `array_key_exists()` en el memo** → reintroduce silenciosamente re-consultas para códigos sin datos en cada llamada dentro del mismo request. Mitigación: test explícito con un código sin ningún indicador registrado.
- **Pasar a `Cache::many()` una lista simple en vez de `[clave => default]`** → pierde la distinción miss/null-cacheado, mismo efecto que el punto anterior. Mitigación: mismo test.
- **`fromSub()` podría no preservar los casts de Eloquent** (`decimal:4`, fecha) al reconstruir el modelo desde la subquery. Mitigación: test que compare valores exactos (no solo conteo de filas) contra el comportamiento actual.
- **Invalidación incompleta entre memo y caché** → el test de `DashboardTest.php` citado arriba lo cubre; si ese test falla tras el cambio, es la señal de que la invalidación quedó desincronizada entre las dos capas.
- **Tests actuales no detectan regresiones de este tipo** (corren bajo `array` store) → el nuevo test bajo `config(['cache.default' => 'database'])` es la única prueba real; sin él, esta mejora quedaría "verificada" solo de palabra.
