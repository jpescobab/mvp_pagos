## 1. IndicadorEconomicoSelector: caché + consulta única

- [x] 1.1 Definir en `IndicadorEconomicoSelector` la clave de caché por tipo (`indicadores_economicos:ultimo:{tipo}`) y un TTL corto (5 minutos) como constante privada.
- [x] 1.2 Reescribir `ultimosPorTipo(array $tipos)` para: resolver primero qué tipos ya tienen entrada de caché vigente, y para los tipos faltantes ejecutar una sola consulta (`whereIn('tipo', $tiposFaltantes)->orderByDesc('fecha_valor')->orderByDesc('periodo')->get()`), agrupando el resultado en PHP por tipo y cacheando cada uno individualmente.
- [x] 1.3 Mantener el shape de retorno actual (`list<array{tipo, valor, fecha_valor, periodo}>`) y el orden de los tipos pedidos en la respuesta.
- [x] 1.4 No modificar `paraFecha()`, `paraPeriodo()` ni `aplicarFallbackUsd()`.

## 2. Invalidación en el importador

- [x] 2.1 En `IndicadorEconomicoImporter::crearIndicador()`, tras `firstOrCreate`, invalidar la entrada de caché `indicadores_economicos:ultimo:{tipo}` del tipo creado únicamente cuando el registro fue realmente creado (`wasRecentlyCreated`).
- [x] 2.2 Verificar que la invalidación cubre los tipos usados en ambos flujos de importación (`importarMensual` y `importarDolarDiario`), dado que ambos pasan por `crearIndicador()`.

## 3. Middleware y controlador

- [x] 3.1 Confirmar que `HandleInertiaRequests::share()` sigue llamando a `IndicadorEconomicoSelector::ultimosPorTipo()` sin cambios de firma (se beneficia automáticamente de la caché al no cambiar la API pública).
- [x] 3.2 Confirmar que `DashboardController::index()` sigue llamando al mismo servicio sin cambios de firma, y que comparte caché con el middleware para los tipos en común (UF, UTM, USD, IPC).

## 4. Tests

- [x] 4.1 Test de `IndicadorEconomicoSelector::ultimosPorTipo()`: primera llamada consulta la base de datos, segunda llamada (mismo tipo, dentro del TTL) no genera consultas adicionales (usar `DB::enableQueryLog()`/`assertQueryCount` o un spy de caché).
- [x] 4.2 Test de invalidación: importar un nuevo valor para un tipo con caché ya poblada, y verificar que `ultimosPorTipo()` retorna el valor recién importado sin esperar el TTL.
- [x] 4.3 Test de que una llamada con varios tipos sin caché vigente resuelve todos en una sola consulta SQL (contar queries antes/después).
- [x] 4.4 Test de regresión sobre el dashboard y el middleware (feature test existente o nuevo) que verifique que los indicadores mostrados siguen siendo los últimos vigentes tras una importación.

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 5.2 `php artisan test --compact --filter=Indicador` (o el filtro que corresponda) para los tests nuevos/afectados.
- [x] 5.3 `composer test` completo antes de cerrar el change.
