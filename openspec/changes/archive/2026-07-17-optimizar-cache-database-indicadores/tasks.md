## 1. Memoización de instancia + singleton

- [x] 1.1 En `IndicadorEconomicoSelector`, agregar propiedad privada `array $resueltosEnRequest = []` para memoizar por código lo ya resuelto (caché o BD) durante la vida de la instancia.
- [x] 1.2 Reescribir `ultimosPorTipo(array $codigos)` para resolver en 3 capas: (1) memo de instancia con `array_key_exists()` (no `isset()`, un código sin datos se memoiza como `null` legítimamente), (2) `Cache::many()` para los códigos no memoizados, (3) `resolverUltimosPorTipo()` solo para los códigos realmente sin caché vigente. Todo lo resuelto en cualquier capa se escribe también al memo antes de retornar.
- [x] 1.3 Actualizar `invalidarUltimoPorTipo(string $codigo)` para limpiar tanto `$resueltosEnRequest[$codigo]` (con `unset`) como la entrada de caché (`Cache::forget`, sin cambios en esa parte).
- [x] 1.4 Registrar `IndicadorEconomicoSelector::class` como singleton en `AppServiceProvider::register()` (`$this->app->singleton(IndicadorEconomicoSelector::class);`).
- [x] 1.5 No modificar `paraFecha()`, `paraPeriodo()` ni `aplicarFallbackUsd()`.

## 2. Cache::many/putMany en vez del loop por código

- [x] 2.1 Sustituir el `foreach` de `Cache::get()` individual por una sola llamada `Cache::many($claves)`, donde `$claves` es un array asociativo `[cacheKey => self::CACHE_MISS, ...]` para los códigos pendientes tras el memo (preserva el sentinel actual).
- [x] 2.2 Sustituir el `foreach` de `Cache::put()` individual por una sola llamada `Cache::putMany($valores, now()->addMinutes(self::CACHE_TTL_MINUTOS))`.
- [x] 2.3 Mantener el shape de retorno actual de `ultimosPorTipo()` (`list<array{codigo, valor, fecha_valor, periodo}>`) y el orden de los códigos pedidos.

## 3. resolverUltimosPorTipo() con función de ventana

- [x] 3.1 Reescribir `resolverUltimosPorTipo(array $codigos)` para traer solo la fila más reciente por código en SQL: subquery con `selectRaw('ROW_NUMBER() OVER (PARTITION BY codigo ORDER BY fecha_valor DESC, periodo DESC) AS rn')` sobre `IndicadorEconomico::query()->whereIn('codigo', $codigos)`, envuelta con `fromSub()` y filtrada por `rn = 1`.
- [x] 3.2 Verificar que el resultado preserva los casts de Eloquent (`decimal`, fecha) y el shape `array<codigo, array{codigo, valor, fecha_valor, periodo}>` idéntico al actual.
- [x] 3.3 No usar `DISTINCT ON` (exclusivo de PostgreSQL) — debe funcionar igual en SQLite (tests) y PostgreSQL (local/prod).

## 4. Tests

- [x] 4.1 En `tests/Feature/Indicadores/IndicadorEconomicoSelectorTest.php`, agregar tests forzando `config(['cache.default' => 'database']);` al inicio (la tabla `cache` ya migra en el sqlite `:memory:` de test) que, con `DB::enableQueryLog()`, verifiquen: (a) resolución fría de un código sin caché consulta tanto la tabla `cache` como `indicadores_economicos`, (b) una segunda llamada sobre la misma instancia con el mismo código no genera ninguna query (memo hit), (c) dos llamadas con códigos parcialmente solapados sobre la misma instancia — la segunda solo genera queries por el código nuevo, no por los que se solapan.
- [x] 4.2 Agregar un test con un código sin ningún indicador registrado, confirmando que se memoiza como `null` y no se re-consulta en una segunda llamada dentro del mismo test (cubre el riesgo `isset()` vs `array_key_exists()`).
- [x] 4.3 Agregar un test que compare valores exactos (no solo conteo de filas) entre el comportamiento actual y el nuevo `resolverUltimosPorTipo()`, para varios códigos con múltiples filas históricas cada uno.
- [x] 4.4 Confirmar que los tests existentes de `IndicadorEconomicoSelectorTest.php` siguen pasando sin modificación.
- [x] 4.5 Confirmar que `tests/Feature/DashboardTest.php` (incluido el test de invalidación con dos requests simulados en el mismo test) sigue pasando sin modificación.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 5.2 `php artisan test --compact --filter=Indicador`.
- [x] 5.3 `php artisan test --compact --filter=Dashboard`.
- [x] 5.4 `composer test` completo antes de cerrar el change.
