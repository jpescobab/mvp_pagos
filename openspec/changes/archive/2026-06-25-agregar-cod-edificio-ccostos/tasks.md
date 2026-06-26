## 1. Migración y modelo

- [x] 1.1 Editar `database/migrations/2026_06_25_223230_create_ccostos_table.php` para agregar `cod_edificio` (string, nullable) en `up()`.
- [x] 1.2 Agregar `cod_edificio` a `$fillable` en `app/Models/Ccosto.php`.

## 2. Reconstrucción y re-siembra

- [x] 2.1 Ejecutar `php artisan migrate:fresh` contra PostgreSQL.
- [x] 2.2 Ejecutar `php artisan db:seed` y confirmar que la cadena completa de seeders sigue corriendo sin error.

## 3. Validación

- [x] 3.1 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
