## Why

La tarea 4 del harness (`tasks/04_implementar_indicadores_economicos.md`) requiere importar UF, USD, UTM, UTA e IPC desde la API oficial de la CMF para que cálculos, reportes y cortes futuros tengan una fuente trazable de indicadores económicos. Hoy no existe ninguna integración externa en el proyecto — esta es la primera tarea que llama a una API real.

## What Changes

- Migraciones nuevas: `indicadores_economicos_importaciones` (registro de cada ejecución de import) e `indicadores_economicos` (valores normalizados, vinculados a su importación vía `importacion_id`).
- `app/Services/Cmf/CmfClient.php`: cliente HTTP hacia `api.cmfchile.cl` (dólar, UF por mes, UTM por año), con parseo de números en formato chileno (`40.809,44` -> `40809.44`).
- `app/Services/Indicadores/IndicadorEconomicoImporter.php`: orquesta la importación mensual (UF del tramo día-10-a-día-9, UTM, UTA calculada, IPC) y la diaria (USD), normaliza y persiste con snapshot (`source_payload`, `source_hash`, `endpoint`, advertencias, errores).
- `app/Services/Indicadores/IndicadorEconomicoSelector.php`: selecciona el indicador correcto por `fecha_valor` (UF/USD) o `periodo` (UTM/UTA/IPC); aplica la regla de fallback parametrizada (`config('indicadores.usd_fallback')`) cuando no hay valor exacto para USD.
- `app/Jobs/ImportarIndicadoresMensualesJob.php` (día 10 de cada mes) y `app/Jobs/ImportarDolarDiarioJob.php` (diario), registrados en `routes/console.php`.
- `CMF_API_KEY` en `.env`/`.env.example` y `config/services.php`; `config/indicadores.php` con la regla de fallback de USD.
- **UTA no es un endpoint de la CMF** (verificado: devuelve HTTP 302) — se calcula como `UTM(diciembre del año comercial) × 12`, fórmula legal estándar (SII).
- Tests con `Http::fake()` — no se llama a la API real en la suite de tests.

## Capabilities

### New Capabilities

- `indicadores-economicos-cmf-sii`: formaliza el spec libre existente (`openspec/specs/indicadores-economicos-cmf-sii/spec.md`) al formato estructurado de OpenSpec.

### Modified Capabilities

(ninguna)

## Impact

- 2 migraciones nuevas, 2 modelos, 3 servicios, 2 jobs.
- `config/services.php`, `config/indicadores.php` nuevos/modificados.
- `routes/console.php`: agrega la programación de ambos jobs.
- `.env.example`: agrega `CMF_API_KEY` vacío (la key real solo vive en `.env`, nunca en Git).
- No afecta ninguna tabla ni dominio de las tareas 1-3.
