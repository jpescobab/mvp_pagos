## Why

Los nombres de archivo de los documentos importados desde SGF se guardan con codificación rota (UTF-8 leído como Latin-1): `CT NÂ°957_433 CONST.EHG.pdf`, `pasaje_aÃ©reo (8).pdf`. El nombre corrupto se usa tal cual como título, `nombre_archivo`, `ruta_archivo` y nombre en disco, y se ve así en el expediente documental. Hoy afecta a 6 documentos de 4 casos reales.

## What Changes

- El scraper y el backend reparan el mojibake del nombre de archivo en el punto de ingesta, de modo que título, `nombre_archivo`, `ruta_archivo` y archivo físico queden consistentes y con la codificación correcta.
- La reimportación sigue siendo idempotente: un documento ya registrado con el nombre corrupto no se duplica al reimportar con el nombre reparado.
- Nuevo comando Artisan idempotente (con `--dry-run`) que repara los registros ya importados y renombra sus archivos físicos, con auditoría.
- El payload crudo del snapshot SGF NO se modifica (evidencia inmutable).
- Fuera de alcance: refetch 417 y fuga de `access_token` en errores (ya corregidos en `3c202b9`, `eb13a20` y `enmascararUrl`).

## Capabilities

### New Capabilities

Ninguna.

### Modified Capabilities

- `conector-sgf-playwright`: se agregan requisitos sobre la codificación correcta de los nombres de archivo de documentos descargados y su reparación en datos ya importados.

## Impact

- `services/sgf-playwright/sgf-scraper.js` (`descargarDocumentosDeFila`), nuevo helper de reparación de nombres.
- `app/Services/Sgf/ConectorSgfPlaywrightService.php` (`vincularDocumento`), nuevo servicio `app/Services/Sgf/ReparadorNombreArchivoSgf.php`.
- Nuevo comando en `app/Console/Commands/` para la reparación de datos existentes.
- Tests Pest en `tests/Feature/Sgf/`. Sin migraciones ni cambios de frontend.
