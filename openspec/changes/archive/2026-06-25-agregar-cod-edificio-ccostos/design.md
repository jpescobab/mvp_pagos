## Context

`ccostos` se creó en el change `core-institucional-capj` (ya archivado). El usuario pidió agregar `cod_edificio` (nullable) y, siguiendo la regla ya establecida de no acumular parches mientras el proyecto está en construcción, se fusiona directamente en la migración original en vez de crear una migración `add_column` separada.

## Goals / Non-Goals

**Goals:**
- Agregar `cod_edificio` como columna nullable de `ccostos`, disponible para uso futuro.
- Mantener la migración de `ccostos` unificada (una sola migración `create_ccostos_table`).

**Non-Goals:**
- No se siembra ningún valor real de `cod_edificio` en esta tarea — no se tiene el dato de origen todavía.
- No se modela una tabla `edificios` separada ni una FK — `cod_edificio` es un código de texto libre por ahora, igual que los demás campos `codigo` de la jerarquía.

## Decisions

- **Editar la migración original `create_ccostos_table`** en vez de agregar una migración nueva, consistente con la decisión tomada para `jurisdicciones.descripcion`. Requiere `migrate:fresh` + re-seed.
- **`cod_edificio` como `string` nullable**, sin `unique` ni FK — es un dato descriptivo/referencial, no una clave de la jerarquía institucional.

## Risks / Trade-offs

- **[Riesgo] `migrate:fresh` vuelve a borrar todos los datos sembrados hasta ahora** → Mitigación: aceptado (proyecto en construcción); se re-siembra inmediatamente con los seeders existentes, sin pérdida de definición de datos.
