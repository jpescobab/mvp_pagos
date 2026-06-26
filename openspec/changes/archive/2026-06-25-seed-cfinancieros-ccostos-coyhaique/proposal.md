## Why

La jurisdicción inicial (`codigo='14'`, "Zonal Coyhaique") está sembrada pero sin centros financieros ni centros de costo reales — la jerarquía CAPJ existe vacía. El usuario tiene esos datos reales en seeders de otro proyecto Laravel (`C:\laragon\www\erp`, tablas planas `cfinancieros`/`ccostos`) y necesita que se reproduzcan aquí, ya remapeados a la jerarquía institucional (`jurisdiccion_id`, `cfinanciero_id`) en vez de las referencias por código que usaba ese proyecto.

## What Changes

- Nuevo seeder `CfinancierosSeeder`: siembra 6 centros financieros reales (códigos `1400`, `1401`, `1402`, `1431`, `1451`, `1471`) bajo la jurisdicción `14` (Zonal Coyhaique).
- Nuevo seeder `CcostosSeeder`: siembra 31 centros de costo reales, cada uno resuelto a su `cfinanciero_id` mediante el `codigo` del centro financiero (no por id literal, que no es portable entre entornos).
- Corrige un error de codificación de caracteres en el nombre del centro de costo `1471031301` presente en los datos de origen (mojibake `Ã` -> texto correcto `JUZGADO DE LETRAS, GARANTÍA Y FAMILIA DE AISÉN`).
- `DatabaseSeeder.php`: encadena los nuevos seeders después de `CoreInstitucionalSeeder`.
- Tests que verifican conteo, pertenencia jerárquica correcta y que el nombre corregido se sembró sin el error de codificación.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `core-institucional-capj`: agrega el requisito de que el seeder pueble centros financieros y centros de costo reales bajo la jurisdicción inicial, no solo institución + jurisdicción.

## Impact

- Nuevos archivos: `database/seeders/CfinancierosSeeder.php`, `database/seeders/CcostosSeeder.php`, tests en `tests/Feature/CoreInstitucional/`.
- `database/seeders/DatabaseSeeder.php`: agrega 2 llamadas a `$this->call(...)`.
- No crea tablas nuevas ni modifica migraciones; usa las tablas `cfinancieros`/`ccostos` ya existentes de la tarea 1.
- Fuente de datos: `C:\laragon\www\erp\database\seeders\CfinancierosSeeder.php` y `CcostosSeeder.php` (proyecto externo, solo como referencia de datos — no se copia su código ni su esquema plano).
