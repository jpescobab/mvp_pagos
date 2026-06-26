## Why

Hoy solo existe una jurisdicción sembrada (`14`, "Zonal Coyhaique"). El usuario aportó el seeder real de otro proyecto Laravel (`C:\laragon\www\trifu\database\seeders\JurisdiccionesSeeder.php`) con la lista completa de las 20 jurisdicciones del Poder Judicial (las 18 zonales + Nivel Central + Sin Clasificar, más CAPJ como jurisdicción `00`). Sin esto, cualquier dominio que dependa de jurisdicciones reales (tarea 2 en adelante) no tiene datos sobre los cuales construir.

## What Changes

- Migración nueva: agrega columna `descripcion` (string, nullable) a `jurisdicciones` (no existe hoy; el origen siempre la trae `null`, pero se deja disponible para uso futuro).
- Nuevo seeder `JurisdiccionesSeeder`: siembra las 20 jurisdicciones reales (`00` a `18`, `99`) bajo la institución CAPJ, vía `firstOrCreate` por `codigo` — preserva la jurisdicción `14` ya sembrada con su nombre actual ("Zonal Coyhaique") en vez de sobrescribirla con el nombre de la lista de origen ("Coyhaique").
- La jurisdicción `00` se siembra con nombre completo "Corporación Administrativa del Poder Judicial" (CAPJ es el mismo acrónimo, por decisión explícita del usuario), como jurisdicción adicional bajo la institución CAPJ — no se fusiona con la tabla `instituciones`.
- `DatabaseSeeder.php`: encadena `JurisdiccionesSeeder` después de `CoreInstitucionalSeeder` y antes de los seeders de centros financieros/costo.
- Tests que verifican el conteo (20 jurisdicciones), que `14` conserva "Zonal Coyhaique", y que `00` quedó con el nombre completo.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `core-institucional-capj`: agrega el requisito de sembrar la lista nacional completa de jurisdicciones, preservando los datos ya sembrados de la jurisdicción inicial.

## Impact

- Nueva migración: `add_descripcion_to_jurisdicciones_table`.
- Nuevo archivo: `database/seeders/JurisdiccionesSeeder.php`, tests en `tests/Feature/CoreInstitucional/`.
- `database/seeders/DatabaseSeeder.php`: agrega 1 llamada a `$this->call(...)`.
- Esta migración debe ejecutarse antes que el seeder de `seed-cfinancieros-ccostos-coyhaique` (change pendiente), aunque no la bloquea técnicamente porque ese seeder ya asume que la jurisdicción `14` existe.
- Fuente de datos: `C:\laragon\www\trifu\database\seeders\JurisdiccionesSeeder.php` (proyecto externo, solo como referencia de datos).
