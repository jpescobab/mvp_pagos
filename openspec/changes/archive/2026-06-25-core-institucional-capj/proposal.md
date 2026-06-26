## Why

El dominio `core-institucional-capj` (jerarquía `instituciones -> jurisdicciones -> cfinancieros -> ccostos`) está descrito en `HARNESS_IA.md` y en un spec libre (`openspec/specs/core-institucional-capj/spec.md`), pero nunca se implementó ni quedó formalizado en el formato estructurado de OpenSpec. Es la tabla base de la que dependen por FK casi todos los demás dominios (permisos, reportes, casos de pago), por lo que debe implementarse primero, según el orden recomendado del harness.

## What Changes

- Migraciones para `instituciones`, `jurisdicciones`, `cfinancieros`, `ccostos`: `id` interno como PK, `codigo` institucional `unique`, FK encadenada hacia el nivel superior con `onDelete('restrict')`.
- `jurisdicciones.codigo` con valor por defecto `'14'`.
- Modelos Eloquent (`Institucion`, `Jurisdiccion`, `Cfinanciero`, `Ccosto`) con relaciones `hasMany`/`belongsTo` encadenadas.
- Seeder que crea la institución CAPJ (`codigo='CAPJ'`, activa) y la jurisdicción inicial (`codigo='14'`, `nombre='Zonal Coyhaique'`).
- Tests de la jerarquía: institución activa, jurisdicción por defecto, trazabilidad ccosto → institución, unicidad de código, protección contra borrado de un nivel con hijos.
- Formaliza `openspec/specs/core-institucional-capj/spec.md` al formato estructurado de OpenSpec (Purpose/Requirements/Scenario).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `core-institucional-capj`: pasa de spec en prosa libre a spec estructurado (Purpose/Requirements/Scenario), sin cambiar el contenido normativo ya definido en el harness (jerarquía fija, código único, jurisdicción inicial con código `14`).

## Impact

- Nuevas tablas: `instituciones`, `jurisdicciones`, `cfinancieros`, `ccostos`.
- Nuevos archivos: 4 migraciones, 4 modelos en `app/Models/`, 1 seeder, 1 archivo de tests Feature.
- `database/seeders/DatabaseSeeder.php`: agrega la llamada al nuevo seeder.
- No afecta auth/Fortify, settings, ni ninguna otra área ya implementada del starter kit.
