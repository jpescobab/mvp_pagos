## 1. Migración

- [x] 1.1 Editar `database/migrations/2026_06_25_223227_create_jurisdicciones_table.php` para agregar `descripcion` (string, nullable) directamente en `up()`.
- [x] 1.2 Eliminar `database/migrations/2026_06_25_232423_add_descripcion_to_jurisdicciones_table.php`.

## 2. Reconstrucción y re-siembra

- [x] 2.1 Ejecutar `php artisan migrate:fresh` contra PostgreSQL.
- [x] 2.2 Ejecutar `php artisan db:seed --class=CoreInstitucionalSeeder` y `--class=JurisdiccionesSeeder`, confirmar institución CAPJ + 20 jurisdicciones con `descripcion` ya parte de la tabla desde el origen.

## 3. Validación

- [x] 3.1 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
