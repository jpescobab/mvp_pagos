## 1. Búsqueda por RUT normalizado

- [x] 1.1 En `app/Http/Controllers/Maestros/ProveedorController.php` (`index()`), calcular `Proveedor::normalizarRut($q)` y agregar `orWhere('rutproveedor', 'like', "%{$rutNormalizado}%")` dentro del grupo de búsqueda, **solo cuando** `$rutNormalizado !== ''`, conservando las comparaciones existentes por `rutproveedor` y `nombre`.

## 2. Tests (Pest)

- [x] 2.1 Test: buscar un RUT con puntos (`77.634.019-7`) encuentra al proveedor cuyo `rutproveedor` está almacenado normalizado (`77634019-7`).
- [x] 2.2 Test: buscar el RUT sin puntos sigue encontrándolo (no regresión, ya cubierto por el test existente "buscar por rut").
- [x] 2.3 Test: buscar un término de nombre no trae todo el catálogo (la normalización vacía no agrega un `LIKE "%%"`).

## 3. Validación y cierre

- [x] 3.1 `vendor/bin/pint --dirty` sobre el controlador modificado.
- [x] 3.2 `composer test` en verde (Pint + PHPStan + Pest) — 619 tests, 615 passed, 4 skipped, 0 failed; PHPStan 0 errores.
- [x] 3.3 `npx openspec validate buscar-proveedor-por-rut-normalizado --strict` en verde.
- [x] 3.4 Verificación con datos reales: buscar `77.634.019-7` en el catálogo devuelve al proveedor `981`.
