## Context

El usuario aportó dos seeders reales de otro proyecto Laravel (`C:\laragon\www\erp`): `CfinancierosSeeder.php` (6 filas, columnas `cfinanciero`/`nombre`) y `CcostosSeeder.php` (31 filas, columnas `ccosto`/`nombre`/`cfinanciero`). Ese proyecto usa tablas planas sin la jerarquía CAPJ (sin `jurisdiccion_id` ni relaciones); referencia el centro financiero de cada centro de costo por su código de negocio, no por id interno.

## Goals / Non-Goals

**Goals:**
- Reproducir los mismos datos reales (códigos y nombres) dentro de nuestra jerarquía CAPJ ya implementada.
- Resolver las referencias por código (no por id) para que el seeder no dependa del orden de inserción ni de ids específicos.
- Dejar el seeder idempotente (reejecutable sin duplicar filas).

**Non-Goals:**
- No se migran más jurisdicciones, instituciones ni centros de costo de otras zonas — solo los de Zonal Coyhaique (jurisdicción `14`) que es la única sembrada hoy.
- No se copia el esquema ni el código del proyecto `erp`; solo los valores de datos.

## Decisions

- **Resolución de FK por `codigo`, no por id.** `CcostosSeeder` busca el `cfinanciero_id` real haciendo `Cfinanciero::where('codigo', $codigoOrigen)->first()`, igual que en `CfinancierosSeeder` se resuelve la `jurisdiccion_id` de la jurisdicción `14`. Alternativa descartada: hardcodear ids — no es portable entre entornos (los ids dependen del orden de ejecución de seeders/migraciones).
- **`firstOrCreate` por `codigo`** en vez de `insertOrIgnore` (que usaba el proyecto origen). `insertOrIgnore` ignora todo el registro si el código ya existe, incluso si el nombre cambió; `firstOrCreate` es más explícito sobre qué campo identifica unicidad y es consistente con `CoreInstitucionalSeeder`.
- **Corrección del nombre `1471031301`**: el dato de origen tiene mojibake (`LETRAS GTÃA. Y FAMILIA AYSÃN`). Se corrige a `JUZGADO DE LETRAS, GARANTÍA Y FAMILIA DE AISÉN`, confirmado con el usuario, en vez de propagar el error de codificación a un registro institucional nuevo.
- **Dos seeders separados** (`CfinancierosSeeder`, `CcostosSeeder`), igual a la separación del proyecto origen, en vez de una sola clase — mantiene cada uno enfocado en una tabla y permite reordenar/reusar independientemente.

## Risks / Trade-offs

- **[Riesgo] Los datos reales podrían tener más correcciones de codificación no detectadas** → Mitigación: se revisó manualmente el texto completo de las 31 filas; solo se encontró el caso de `1471031301`. Si aparecen más casos al validar contra la fuente oficial, se corrigen en una migración de datos posterior, no retroactivamente en este seeder sin registro.
- **[Riesgo] El seeder depende de que `CoreInstitucionalSeeder` haya corrido antes** (jurisdicción `14` debe existir) → Mitigación: se encadena explícitamente después en `DatabaseSeeder.php`; si la jurisdicción no existe, el seeder falla de forma clara (no crea datos huérfanos).
