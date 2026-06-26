## 1. Seeder de centros financieros

- [x] 1.1 Crear `database/seeders/CfinancierosSeeder.php`: resuelve la jurisdicción `14` y siembra (vía `firstOrCreate` por `codigo`) los 6 centros financieros reales (`1400` Administracion Zonal, `1401` Garantía, `1402` Oral, `1431` Laboral, `1451` Familia, `1471` Competencia Común).

## 2. Seeder de centros de costo

- [x] 2.1 Crear `database/seeders/CcostosSeeder.php`: para cada una de las 31 filas de origen, resuelve el `cfinanciero_id` por `codigo` y siembra (vía `firstOrCreate` por `codigo`) el centro de costo.
- [x] 2.2 Aplicar la corrección de nombre acordada para `1471031301`: `JUZGADO DE LETRAS, GARANTÍA Y FAMILIA DE AISÉN`.

## 3. Wiring

- [x] 3.1 Encadenar `CfinancierosSeeder` y `CcostosSeeder` desde `DatabaseSeeder.php`, después de `CoreInstitucionalSeeder`.

## 4. Tests

- [x] 4.1 Test: el seeder crea los 6 centros financieros esperados bajo la jurisdicción `14`.
- [x] 4.2 Test: el seeder crea los 31 centros de costo esperados, cada uno con el `cfinanciero_id` correcto.
- [x] 4.3 Test: el nombre de `1471031301` quedó sembrado sin el error de codificación.

## 5. Validación

- [x] 5.1 Ejecutar `php artisan migrate:fresh --seed` contra PostgreSQL y confirmar conteos esperados (6 cfinancieros, 31 ccostos).
- [x] 5.2 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
