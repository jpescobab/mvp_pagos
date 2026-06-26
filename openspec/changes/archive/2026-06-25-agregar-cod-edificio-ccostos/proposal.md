## Why

Varios centros de costo (ej. los 20 "VIVIENDA JUDICIAL") corresponden a inmuebles físicos concretos. Se necesita un campo para vincular opcionalmente cada centro de costo a un código de edificio, sin que sea obligatorio para los centros de costo que no representan un inmueble.

## What Changes

- Agrega `cod_edificio` (string, nullable) a la tabla `ccostos`, fusionado directamente en la migración original `create_ccostos_table` (de la tarea 1, ya archivada) en vez de una migración separada — el proyecto está en construcción y no hay datos de producción que justifiquen un parche.
- `migrate:fresh` + re-siembra completa de los seeders institucionales.
- No se siembra ningún valor de `cod_edificio` todavía (todos los registros existentes no tienen ese dato de origen); queda disponible para cuando se tenga la información real.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `core-institucional-capj`: agrega el requisito de que un centro de costo pueda asociarse opcionalmente a un código de edificio.

## Impact

- `database/migrations/2026_06_25_223230_create_ccostos_table.php`: agrega columna `cod_edificio`.
- `app/Models/Ccosto.php`: agrega `cod_edificio` a `$fillable`.
- No afecta seeders existentes (no se les pide sembrar este dato todavía) ni ningún otro dominio.
