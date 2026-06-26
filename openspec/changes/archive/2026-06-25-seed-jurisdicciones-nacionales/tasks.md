## 1. Migración

- [x] 1.1 Crear migración `add_descripcion_to_jurisdicciones_table`: agrega columna `descripcion` (string, nullable) a `jurisdicciones`.

## 2. Seeder

- [x] 2.1 Crear `database/seeders/JurisdiccionesSeeder.php`: siembra (vía `firstOrCreate` por `codigo`) las 20 jurisdicciones reales (`00` a `18`, `99`) asociadas a la institución CAPJ, con `descripcion` en `null`.
- [x] 2.2 Confirmar que la jurisdicción `00` queda con nombre completo "Corporación Administrativa del Poder Judicial".
- [x] 2.3 Encadenar `JurisdiccionesSeeder` desde `DatabaseSeeder.php`, después de `CoreInstitucionalSeeder`.

## 3. Tests

- [x] 3.1 Test: el seeder crea las 20 jurisdicciones esperadas.
- [x] 3.2 Test: si la jurisdicción `14` ya existe con nombre "Zonal Coyhaique", el seeder no la sobrescribe.
- [x] 3.3 Test: la jurisdicción `00` queda con el nombre completo de CAPJ.

## 4. Validación

- [x] 4.1 Ejecutar `php artisan migrate` y `php artisan db:seed` contra PostgreSQL, confirmar 20 jurisdicciones. (verificado vía `db:seed --class=JurisdiccionesSeeder`, 20 filas, `14` conserva "Zonal Coyhaique", `00` con nombre completo)
- [x] 4.2 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
