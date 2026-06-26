## Context

`jurisdicciones` se creó en el change `core-institucional-capj` (ya archivado) y luego se le agregó la columna `descripcion` en un change posterior (`seed-jurisdicciones-nacionales`, también archivado) mediante una migración separada (`add_descripcion_to_jurisdicciones_table`). El usuario pidió explícitamente no acumular migraciones "parche" mientras el proyecto está en construcción y sin datos de producción.

## Goals / Non-Goals

**Goals:**
- Dejar `descripcion` como parte de la migración original `create_jurisdicciones_table`, no como un parche posterior.
- Eliminar la migración `add_descripcion_to_jurisdicciones_table`.
- Reconstruir la base de datos desde cero (`migrate:fresh`) y volver a sembrar todo, ya que no hay datos que preservar.

**Non-Goals:**
- No se cambia ningún comportamiento observable, requirement ni scenario — `descripcion` sigue nullable, los seeders siguen igual.
- No se toca ninguna otra tabla/migración de la jerarquía CAPJ (`instituciones`, `cfinancieros`, `ccostos` quedan igual).

## Decisions

- **Editar directamente la migración original** en vez de crear una migración `down()` que revierta la columna — el principio "no parches" aplica también a no encadenar una migración de limpieza sobre otra; se edita la fuente y se reconstruye la base.
- **`migrate:fresh` en vez de rollback selectivo.** Alternativa descartada: hacer rollback manual de las migraciones afectadas en orden inverso — más frágil y propenso a error que simplemente reconstruir todo, dado que no hay datos reales que conservar todavía.
- **Re-sembrar con los seeders existentes sin modificarlos.** `CoreInstitucionalSeeder`, `JurisdiccionesSeeder` no necesitan cambios de código — ya escriben `descripcion => null`, columna que ahora existe desde el origen de la tabla en vez de agregada después.

## Risks / Trade-offs

- **[Riesgo] `migrate:fresh` borra toda la data actual** (institución, 20 jurisdicciones) → Mitigación: aceptado explícitamente por el usuario ("estamos en construcción"); se re-siembra inmediatamente después con los mismos seeders, sin pérdida de definición de datos (solo se reconstruyen los registros).
