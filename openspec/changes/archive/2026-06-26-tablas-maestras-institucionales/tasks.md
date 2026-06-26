## 1. Migraciones

- [x] 1.1 `create_items_table`: id, codigo (unique), nombre, descripcion (nullable), activo, soft deletes, timestamps.
- [x] 1.2 `create_asignaciones_table`: id, item_id (FK -> items, restrict), codigo (unique), nombre, descripcion (nullable), activo, soft deletes, timestamps.
- [x] 1.3 `create_catalogos_table`: id, item_id (FK -> items, restrict), codigo (unique), nombre, descripcion (nullable), activo, soft deletes, timestamps.
- [x] 1.4 `create_proveedores_table`: id, rutproveedor (unique), nombre, correo (nullable), direccion (nullable), contacto (nullable), imagen (nullable), activo, soft deletes, timestamps.
- [x] 1.5 `create_funcionarios_table`: id, rut (unique), nombre, user_id (nullable FK -> users, nullOnDelete), ccosto_id (nullable FK -> ccostos), cfinanciero_id (nullable FK -> cfinancieros), activo, soft deletes, timestamps.
- [x] 1.6 `create_clientes_medidores_table`: id, numero_cliente (unique), proveedor_id (nullable FK -> proveedores, nullOnDelete), ccosto_id (FK -> ccostos, restrict), tipo_suministro, direccion_suministro (nullable), activo, soft deletes, timestamps.

## 2. Modelos

- [x] 2.1 `app/Models/Item.php`: relaciones `asignaciones()`, `catalogos()`.
- [x] 2.2 `app/Models/Asignacion.php`: relación `item()`.
- [x] 2.3 `app/Models/Catalogo.php`: relación `item()`.
- [x] 2.4 `app/Models/Proveedor.php`: relación `clientesMedidores()`.
- [x] 2.5 `app/Models/Funcionario.php`: relaciones `user()`, `ccosto()`, `cfinanciero()`.
- [x] 2.6 `app/Models/ClienteMedidor.php`: relaciones `proveedor()`, `ccosto()`.
- [x] 2.7 PHPDoc genérico (`@return HasMany<Modelo, $this>` / `@return BelongsTo<Modelo, $this>`) en todas las relaciones.

## 3. Conversión de datos reales

- [x] 3.1 Escribir script de conversión que parsee `C:\laragon\www\erp\database\seeders\ProveedoresSeeder.php` (977 filas SQL MySQL) y genere `database/seeders/ProveedoresSeeder.php` con `insertOrIgnore` compatible con PostgreSQL.
- [x] 3.2 Crear `database/seeders/ItemsSeeder.php` con las 12 filas reales.
- [x] 3.3 Crear `database/seeders/AsignacionesSeeder.php` con las 57 filas reales, resolviendo `item_id` por `codigo`.
- [x] 3.4 Crear `database/seeders/CatalogosSeeder.php` con las 156 filas reales, resolviendo `item_id` por `codigo`, mapeando `estado='Activo'` a `activo=true`.
- [x] 3.5 Crear `database/seeders/ClientesMedidoresSeeder.php` con las 39 filas reales, resolviendo `proveedor_id` por `rutproveedor` y `ccosto_id` por `codigo` de `ccostos` (no crear jurisdicciones nuevas).

## 4. Wiring

- [x] 4.1 Encadenar los nuevos seeders en `DatabaseSeeder.php` en orden de dependencia: `ItemsSeeder` → `AsignacionesSeeder`/`CatalogosSeeder`; `ProveedoresSeeder` → `ClientesMedidoresSeeder`.

## 5. Tests

- [x] 5.1 Test: `items`/`asignaciones`/`catalogos` se siembran con los conteos esperados (12/57/156) y las relaciones `item_id` resuelven correctamente.
- [x] 5.2 Test: `proveedores` se siembra con 977 filas y `rutproveedor` es único.
- [x] 5.3 Test: `clientes_medidores` resuelve `ccosto_id` correctamente (no jurisdicción) y `proveedor_id` apunta a la "EMPRESA ELECTRICA DE AYSEN S.A.".
- [x] 5.4 Test: `funcionarios` permite `user_id`/`ccosto_id`/`cfinanciero_id` nulos y `rut` único.

## 6. Validación

- [x] 6.1 Ejecutar `php artisan migrate:fresh --seed` contra PostgreSQL y confirmar todos los conteos esperados.
- [x] 6.2 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
