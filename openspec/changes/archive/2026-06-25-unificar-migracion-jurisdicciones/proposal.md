## Why

El proyecto está en construcción (sin datos de producción ni despliegues), así que no hay razón para mantener una migración separada `add_descripcion_to_jurisdicciones_table` como parche sobre `create_jurisdicciones_table`. Mantener el historial de migraciones limpio (una migración por tabla, sin parches acumulados) evita deuda técnica innecesaria mientras todavía se puede reescribir libremente.

## What Changes

- Fusiona la columna `descripcion` directamente en la migración `create_jurisdicciones_table` (de la tarea 1).
- Elimina la migración separada `add_descripcion_to_jurisdicciones_table`.
- `migrate:fresh` + re-siembra de todos los seeders institucionales (no hay datos reales que preservar).
- No cambia ningún requirement ni comportamiento observable — `descripcion` sigue siendo nullable y los seeders se comportan igual.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

(ninguna — esto es limpieza de migraciones, no un cambio de requisitos)

## Impact

- `database/migrations/2026_06_25_223227_create_jurisdicciones_table.php`: agrega columna `descripcion`.
- Se elimina `database/migrations/2026_06_25_232423_add_descripcion_to_jurisdicciones_table.php`.
- Requiere `migrate:fresh` (se pierde y se rehace toda la data sembrada hasta ahora — aceptable, proyecto en construcción).
