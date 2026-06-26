## 1. Configuración

- [x] 1.1 Agregar `CMF_API_KEY` a `.env` (valor real) y `.env.example` (vacío).
- [x] 1.2 Agregar sección `cmf` a `config/services.php` (`api_key`, `base_url`).
- [x] 1.3 Crear `config/indicadores.php` con `usd_fallback` (default `'ultimo_valor_disponible'`).

## 2. Migraciones y modelos

- [x] 2.1 Migración `create_indicadores_economicos_importaciones_table`: id, tipo, estado, endpoint (nullable), source_payload (json nullable), errores (json nullable), advertencias (json nullable), metadata (json nullable), timestamps.
- [x] 2.2 Migración `create_indicadores_economicos_table`: id, importacion_id (FK restrict), tipo, fecha_valor (nullable), periodo (nullable), valor (decimal), periodicidad_valor, periodicidad_publicacion (nullable), vigente_desde (nullable), vigente_hasta (nullable), fuente, source_url (nullable), source_hash (nullable), source_payload (json nullable), advertencias (json nullable), created_at (sin updated_at); índices únicos `(tipo, fecha_valor)` y `(tipo, periodo)`.
- [x] 2.3 Modelos `app/Models/IndicadorEconomicoImportacion.php` (hasMany `indicadores`) y `app/Models/IndicadorEconomico.php` (belongsTo `importacion`).

## 3. Cliente CMF

- [x] 3.1 `app/Services/Cmf/CmfClient.php`: métodos `dolar()`, `uf(int $anio, int $mes)`, `utm(int $anio)`, `ipc()`, usando `Http::` hacia `config('services.cmf')`.
- [x] 3.2 Helper de parseo de números en formato chileno (`'40.809,44'` -> `40809.44`).

## 4. Importación y selección

- [x] 4.1 `app/Services/Indicadores/IndicadorEconomicoImporter.php::importarMensual()`: tramo de UF (día 10 a día 9 del mes siguiente, combinando 2 meses calendario), UTM del año vigente, IPC, y UTA calculada si la UTM de diciembre está disponible. Crea la `IndicadorEconomicoImportacion` con snapshot.
- [x] 4.2 `IndicadorEconomicoImporter::importarDolarDiario()`: importa USD del día; si la fecha devuelta por la CMF no coincide con hoy, registra advertencia (sin inventar el valor de hoy).
- [x] 4.3 `app/Services/Indicadores/IndicadorEconomicoSelector.php::paraFecha(string $tipo, CarbonInterface $fecha)`: UF exacto; USD con fallback (`config('indicadores.usd_fallback')`) si no hay valor exacto.
- [x] 4.4 `IndicadorEconomicoSelector::paraPeriodo(string $tipo, string $periodo)`: UTM/UTA/IPC por periodo.

## 5. Jobs y scheduling

- [x] 5.1 `app/Jobs/ImportarIndicadoresMensualesJob.php` y `app/Jobs/ImportarDolarDiarioJob.php` (ambos `ShouldQueue`).
- [x] 5.2 Registrar en `routes/console.php`: mensual el día 10, diario.

## 6. Tests (con `Http::fake()`, sin red real)

- [x] 6.1 Test: parseo de números chilenos.
- [x] 6.2 Test: `importarMensual()` crea el tramo de UF correcto (cruzando dos meses calendario) con `vigente_desde`/`vigente_hasta`.
- [x] 6.3 Test: UTA se calcula solo cuando la UTM de diciembre está disponible; no se crea si no lo está.
- [x] 6.4 Test: `importarDolarDiario()` crea el registro USD y registra advertencia si la fecha no coincide con hoy.
- [x] 6.5 Test: `IndicadorEconomicoSelector` aplica el fallback configurado para USD cuando no hay valor exacto.

## 7. Validación

- [x] 7.1 Ejecutar `php artisan migrate` contra PostgreSQL.
- [x] 7.2 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
