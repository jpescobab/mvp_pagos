## 1. Migraciones

- [x] 1.1 Crear migración `create_instituciones_table`: `id`, `codigo` (string, unique), `nombre`, `activo` (boolean, default true), timestamps.
- [x] 1.2 Crear migración `create_jurisdicciones_table`: `id`, `institucion_id` (FK -> instituciones, restrict), `codigo` (string, unique, default `'14'`), `nombre`, `activo`, timestamps.
- [x] 1.3 Crear migración `create_cfinancieros_table`: `id`, `jurisdiccion_id` (FK -> jurisdicciones, restrict), `codigo` (unique), `nombre`, `activo`, timestamps.
- [x] 1.4 Crear migración `create_ccostos_table`: `id`, `cfinanciero_id` (FK -> cfinancieros, restrict), `codigo` (unique), `nombre`, `activo`, timestamps.

## 2. Modelos

- [x] 2.1 Crear `app/Models/Institucion.php` con `$table`, `$fillable`, cast `activo` a boolean, relación `jurisdicciones(): HasMany`.
- [x] 2.2 Crear `app/Models/Jurisdiccion.php` con relaciones `institucion(): BelongsTo` y `cfinancieros(): HasMany`.
- [x] 2.3 Crear `app/Models/Cfinanciero.php` con relaciones `jurisdiccion(): BelongsTo` y `ccostos(): HasMany`.
- [x] 2.4 Crear `app/Models/Ccosto.php` con relación `cfinanciero(): BelongsTo`.
- [x] 2.5 Anotar cada relación con PHPDoc genérico (`@return HasMany<Modelo, $this>` / `@return BelongsTo<Modelo, $this>`) para que PHPStan/Larastan pase sin errores.

## 3. Seeder

- [x] 3.1 Crear `database/seeders/CoreInstitucionalSeeder.php`: institución CAPJ (`codigo='CAPJ'`, activa) y jurisdicción inicial (`codigo='14'`, `nombre='Zonal Coyhaique'`), usando `firstOrCreate` para ser idempotente.
- [x] 3.2 Invocar el seeder desde `database/seeders/DatabaseSeeder.php` con `$this->call(...)`.

## 4. Tests

- [x] 4.1 Test: el seeder crea la institución CAPJ activa y la jurisdicción inicial con código `14`.
- [x] 4.2 Test: registrar un centro de costo guarda `id` interno y es trazable hasta CAPJ (`ccosto -> cfinanciero -> jurisdiccion -> institucion`).
- [x] 4.3 Test: `jurisdicciones.codigo` usa `'14'` por defecto cuando no se especifica.
- [x] 4.4 Test: el código institucional es único (al menos en `instituciones` y `ccostos`, que es el caso explícito del spec).
- [x] 4.5 Test: no se puede eliminar una institución con jurisdicciones asociadas (protección `restrict`).

## 5. Validación

- [x] 5.1 Ejecutar `php artisan migrate` y `php artisan db:seed` contra PostgreSQL y confirmar que corren sin error. (migrate OK; db:seed: `CoreInstitucionalSeeder` corrió OK — falló después en `User::factory` del starter kit por un usuario de prueba ya existente de una corrida previa, no relacionado con esta tarea)
- [x] 5.2 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check` / `npm run types:check`, todo en verde.
