## 1. Migraciones (modificar in-place, no crear alter migrations)

- [x] 1.1 Editar `database/migrations/2026_06_26_175441_create_indicadores_economicos_table.php`: renombrar la columna `tipo` a `codigo`; agregar columna nueva `tipo` (string, categoría: `unidad_reajustable`/`unidad_tributaria`/`moneda`/`indice`); agregar `nombre` (string), `unidad_medida` (string), `moneda_base` (string), `endpoint` (string nullable), `capturado_en` (timestamp nullable), `capturado_por_user_id` (foreignId nullable, `nullOnDelete`, referencia a `users`), `capturado_por_job` (string nullable), `requiere_dia_habil` (boolean default false), `es_proyectado` (boolean default false), `es_oficial` (boolean default true), `activo` (boolean default true), `metadata` (json nullable); eliminar la columna `advertencias`; reemplazar los 2 `unique()` actuales por `unique(['codigo', 'fecha_valor', 'periodo', 'fuente', 'es_proyectado'])`.
- [x] 1.2 Editar `database/migrations/2026_06_26_175440_create_indicadores_economicos_importaciones_table.php`: renombrar `tipo` a `tipo_importacion`; agregar `indicadores_solicitados` (json nullable), `fuente_principal` (string nullable), `fuente_fallback` (string nullable), `fecha_programada` (date nullable), `periodo` (string nullable), `fecha_desde`/`fecha_hasta` (date nullable), `iniciado_en`/`finalizado_en` (timestamp nullable), `creado_por_user_id` (foreignId nullable, `nullOnDelete`, referencia a `users`), `ejecutado_por_job` (string nullable), `total_recibidos`/`total_creados`/`total_omitidos`/`total_fallidos` (integer default 0); eliminar `endpoint` y `source_payload`; el default de `estado` pasa de `'ok'` a `'pending'`.
- [x] 1.3 Corrido `php artisan migrate:fresh` en el entorno de desarrollo.

## 2. Modelos

- [x] 2.1 Actualizado `app/Models/IndicadorEconomico.php`: `$fillable`/casts con los campos nuevos, `codigo` en vez de `tipo`, relación `capturadoPor()`, `UPDATED_AT = null` sin cambios.
- [x] 2.2 Actualizado `app/Models/IndicadorEconomicoImportacion.php`: `$fillable`/casts nuevos, `tipo_importacion`, relación `creadoPor()`, métodos `marcarComoRunning()`/`marcarComoFinalizada()`.

## 3. Servicios nuevos (namespace `App\Services\Indicadores`)

- [x] 3.1 Creado `ServicioNormalizadorIndicadores::normalizarValor()`.
- [x] 3.2 Creado `ServicioPersistenciaIndicadores::crearSiNoExiste()`.
- [x] 3.3 Creado `RegistradorImportacionIndicadores` (contador por ejecución, no compartido — se instancia con `new` dentro de cada llamada de `ServicioImportacionIndicadores`, no vía inyección de contenedor).
- [x] 3.4 Creado `ServicioImportacionIndicadores::importarMensual()`/`importarUsd()`, con parámetro `ejecutadoPorJob` explícito (en vez de inferirlo del `tipoImportacion`) para que jobs/comandos/HTTP indiquen su origen sin ambigüedad.
- [x] 3.5 Eliminado `app/Services/Indicadores/IndicadorEconomicoImporter.php` (sin referencias restantes tras actualizar jobs y tests).
- [x] 3.6 Actualizado `IndicadorEconomicoSelector.php`: `tipo` → `codigo` en parámetros, `where()`, claves de caché y claves del array de retorno (`ultimosPorTipo()` ahora retorna `codigo` en vez de `tipo`, propagado a los consumidores del array).

## 4. Jobs

- [x] 4.1 `ImportarIndicadoresMensualesJob`: inyecta `ServicioImportacionIndicadores`, pasa `ejecutadoPorJob: self::class`, agrega middleware `WithoutOverlapping`.
- [x] 4.2 `ImportarDolarDiarioJob`: mismo patrón para `importarUsd()`.

## 5. Comandos Artisan

- [x] 5.1 Creado `ImportarIndicadoresMensualesCommand` (`indicadores:importar-mensual {--periodo=}`), autodescubierto por Laravel (confirmado con `php artisan list indicadores`).
- [x] 5.2 Creado `ImportarUsdCommand` (`indicadores:importar-usd {--fecha=}`).

## 6. Permiso nuevo y disparo manual HTTP

- [x] 6.1 Agregado `indicadores.importar` al seeder (`superadmin` y `admin`).
- [x] 6.2 Creada `IndicadorEconomicoImportacionPolicy::importar()`, registrada en `AppServiceProvider`.
- [x] 6.3 Agregada `POST /indicadores-economicos/importar-mensual` (ruta renombrada a grupo con prefijo) + método `importarMensual()` en `IndicadorEconomicoController`. Ajuste sobre el plan original: se descartó el "flash message" porque el proyecto no tiene infraestructura de mensajes flash en ningún controlador existente (`HandleInertiaRequests` no comparte `flash`) — se usa `return back()` simple, consistente con el patrón real del resto de la app.
- [x] 6.4 Agregado botón "Importar ahora" en `indicadores-economicos/index.tsx`, condicionado a `auth.permissions.includes('indicadores.importar')`.

## 7. Scheduler

- [x] 7.1 `routes/console.php`: agregado `->timezone('America/Coyhaique')` y `->withoutOverlapping()` a ambos `Schedule::job(...)`.

## 8. Frontend / Resource / Middleware (renombrar `tipo` → `codigo`)

- [x] 8.1 `IndicadorEconomicoResource`: expone `codigo`.
- [x] 8.2 `IndicadorEconomicoController::index()`: filtro por `codigo` (hecho junto con la tarea 6.3).
- [x] 8.3 `HandleInertiaRequests.php`: sin cambios necesarios (la llamada ya pasaba códigos como strings literales, no dependía del nombre del campo).
- [x] 8.4 `types/indicadores.ts`: `codigo` en vez de `tipo`.
- [x] 8.5 `lib/indicadores.ts`: `codigo` en el tipo `Indicador`, `ETIQUETAS_INDICADOR` y `formatearValorIndicador()`.
- [x] 8.6 `topbar-indicadores.tsx`: usa `indicador.codigo`.
- [x] 8.7 `pages/indicadores-economicos/index.tsx`: filtro y tabla usan `codigo`; botón "Importar ahora" agregado. Además se corrigió `resources/js/pages/dashboard.tsx` (no estaba en el plan original, pero consume el mismo tipo `Indicador` de `lib/indicadores.ts` para los chips del panel general — hubiera quedado con una referencia rota a `.tipo`).

## 9. Tests

- [x] 9.1 Adaptado (recreado como `ServicioImportacionIndicadoresTest.php`): `tipo` → `codigo`, `estado` → nuevos valores, verifica `total_*`. Se agregaron además 2 casos no listados originalmente: "la CMF no devuelve ningún valor de USD" (estado `failed`) y "ejecutar importarUsd dos veces no duplica" (idempotencia).
- [x] 9.2 Adaptado `IndicadorEconomicoSelectorTest.php`: `tipo` → `codigo`; el helper `crearIndicador()` ahora agrega los campos NOT NULL nuevos (`nombre`, `tipo` categoría, `unidad_medida`, `moneda_base`) con valores de prueba por defecto.
- [x] 9.3 Adaptado `ConsultarIndicadoresEconomicosTest.php`: filtro por `codigo`.
- [x] 9.4 Cubierto dentro de 9.1 (test de idempotencia de `importarUsd`) y 9.5 (idempotencia de reproceso controlado mensual/diario).
- [x] 9.5 Creado `ReprocesoControladoIndicadoresTest.php`: `--periodo=`/`--fecha=` registran `tipo_importacion = reproceso_controlado` y omiten en la segunda corrida.
- [x] 9.6 Creado `ImportarManualIndicadoresTest.php`: 403 sin `indicadores.importar`, redirect (éxito) con el permiso.
- [x] 9.7 Creado `IndicadoresEconomicosEsquemaTest.php`: verifica que el índice único rechaza un duplicado exacto y que un `fuente` distinto no colisiona. **Este test reveló que el diseño original de un único índice de 5 columnas no funcionaba** (ver design.md, Decisión 3) — quedó corregido antes de que el test pasara.
- [x] 9.8 (No estaba en el plan original) Corregido `tests/Feature/DashboardTest.php`: helper de indicador de prueba actualizado al esquema nuevo, `IndicadorEconomicoImporter` → `ServicioImportacionIndicadores`, 2 aserciones `firstWhere`/`where` con `.tipo` → `.codigo` que se habían pasado por alto en el primer barrido.
- [x] 9.9 (No estaba en el plan original) Corregido `tests/Feature/Seguridad/RolesAndPermissionsSeederTest.php`: agregado `indicadores.importar` a la lista exhaustiva de permisos esperados.
- [x] 9.10 (No estaba en el plan original, descubierto al correr los tests) Corregido el cast de `fecha_valor`/`vigente_desde`/`vigente_hasta` en `IndicadorEconomico` de `'date'` a `'date:Y-m-d'` — el cast sin formato guardaba con hora completa (`Y-m-d H:i:s`) pero `firstOrCreate()` buscaba con el string `Y-m-d` plano, rompiendo la detección de duplicados en SQLite (en Postgres quedaba enmascarado porque su columna `DATE` nativa trunca la hora). Ver design.md, Decisión 3.1.

## 10. Validación

- [x] 10.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados. Passed.
- [x] 10.2 `npm run types:check` y `npm run lint:check` sobre el frontend. Sin errores.
- [x] 10.3 `php artisan test --compact --filter=Indicadores`. 26/26 passed. También se corrió `--filter=DashboardTest` (4/4 passed) por los ajustes cruzados.
- [x] 10.4 Verificado en el navegador (build de producción + `sadmin@pjud.cl`): al probar en vivo, la UF fallaba con 404 real de la CMF porque el tramo consultado (10-jul a 9-ago) todavía no existía — la fecha de esa sesión de pruebas cae antes del día 10 del mes, y `importarTramoUf()` calculaba siempre el tramo del mes calendario actual sin importar si "hoy" ya lo alcanzó. **Bug real, no un caso esperado** (a diferencia de lo que se documentó en un primer verificado — ver 9.11). Corregido: si "hoy" cae antes del día 10, el tramo vigente es el que empezó el día 10 del mes ANTERIOR. Reverificado en el navegador tras el fix: UF importa 30 registros reales, `estado=success`, 0 fallidos.
- [x] 9.11 (No estaba en el plan original, reportado por el usuario tras la primera verificación en navegador) `ServicioImportacionIndicadores::importarTramoUf()`: `$hoy->setDay(10)` saltaba al día 10 del mes calendario en curso sin comprobar si "hoy" ya pasó ese día — si no, el tramo consultado corresponde a fechas futuras que la CMF aún no publica (404 "no hay datos disponibles"), y el usuario se quedaba sin valor de UF. Corregido para usar el tramo del mes anterior cuando `hoy->day < 10`. Se agregó `tests/Feature/Indicadores/ServicioImportacionIndicadoresTest.php::'importarMensual usa el tramo del mes anterior si hoy cae antes del día 10'`, que habría detectado el bug (el test existente solo cubría el caso "hoy" >= día 10).
- [x] 10.5 `composer test` completo. Pint ✓, PHPStan 0 errores (tras corregir un tipo de PHPDoc en `marcarComoFinalizada()`), Pest 386 tests (382 passed, 4 skipped preexistentes) tras el fix del tramo UF.
