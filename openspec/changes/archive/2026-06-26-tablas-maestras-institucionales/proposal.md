## Why

La tarea 2 del harness (`tasks/02_implementar_tablas_maestras_institucionales.md`) requiere las tablas maestras institucionales (proveedores, funcionarios, clientes medidores, items, catálogos, asignaciones) sobre las que se apoyan el resto de los dominios — en particular Pago de Proveedores (tarea 8) y Consumo Eléctrico. El usuario aportó los seeders reales de otro proyecto Laravel (`C:\laragon\www\erp`) con datos reales de CAPJ Zonal Coyhaique para 5 de las 6 tablas.

## What Changes

- Migraciones nuevas: `items`, `asignaciones`, `catalogos`, `proveedores`, `funcionarios`, `clientes_medidores`. Todas con `id` interno, código/identificador único, `activo` boolean, soft deletes, timestamps.
- `asignaciones.item_id` y `catalogos.item_id`: FK a `items` (catálogo se relaciona directo con ítem, replicando la estructura real del origen — no existe `asignacion_id` en catálogos, ni en los datos de origen).
- `clientes_medidores.proveedor_id` (nullable, FK a `proveedores`) y `clientes_medidores.ccosto_id` (FK a `ccostos`, NO a jurisdicción — corrige una confusión de nombres del proyecto origen, donde su modelo "Jurisdiccion" en realidad representa lo que en este proyecto ya son `ccostos`).
- `funcionarios.user_id` (nullable, FK a `users`), `funcionarios.ccosto_id`/`cfinanciero_id` (nullable) — solo esquema, sin datos reales todavía.
- Modelos Eloquent para las 6 tablas con sus relaciones.
- Seeders con datos reales para `items` (12), `asignaciones` (57), `catalogos` (156), `proveedores` (977, convertidos desde SQL MySQL del origen a `insertOrIgnore` compatible con PostgreSQL), `clientes_medidores` (39, resueltos a `ccosto_id` por código en vez del modelo "Jurisdiccion" ad-hoc del origen).
- Tests de relaciones, unicidad y de la corrección de mapeo de `clientes_medidores`.

## Capabilities

### New Capabilities

- `tablas-maestras-institucionales`: formaliza el spec libre existente (`openspec/specs/tablas-maestras-institucionales/spec.md`) al formato estructurado de OpenSpec.

### Modified Capabilities

(ninguna — no se modifica `core-institucional-capj`, solo se referencia su tabla `ccostos` vía FK)

## Impact

- 6 migraciones nuevas, 6 modelos nuevos, 5 seeders con datos reales + 1 sin datos (`funcionarios`).
- `database/seeders/DatabaseSeeder.php`: encadena los nuevos seeders, respetando el orden de dependencia (`proveedores` antes de `clientes_medidores`; `items` antes de `asignaciones`/`catalogos`).
- Fuente de datos: seeders reales de `C:\laragon\www\erp` (proyecto externo, solo como referencia de datos).
- No se toca ninguna tabla de la tarea 1 (`instituciones`, `jurisdicciones`, `cfinancieros`, `ccostos`).
