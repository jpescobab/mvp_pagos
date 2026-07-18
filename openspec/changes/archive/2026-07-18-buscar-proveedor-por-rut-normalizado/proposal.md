## Why

El catálogo de proveedores no encuentra a un proveedor cuando se busca por su RUT con el formato natural (con puntos, como `77.634.019-7`), porque el RUT se almacena **normalizado sin puntos** (`77634019-7`) pero la búsqueda compara el término tal cual con `LIKE`. Resultado: buscar `77.634.019-7` devuelve 0 resultados aunque el proveedor exista, lo que hace parecer que un proveedor recién creado (p. ej. al guardar una OC de Mercado Público) no se guardó.

## What Changes

- La búsqueda del catálogo de proveedores (`ProveedorController::index`) SHALL encontrar a un proveedor por su RUT **sin importar el formato** del término (con o sin puntos ni guión): además de comparar el término tal cual, compara `rutproveedor` contra `Proveedor::normalizarRut($término)` cuando esa normalización no queda vacía.
- La búsqueda por nombre no cambia.

## Capabilities

### New Capabilities

_(ninguna)_

### Modified Capabilities

- `consulta-catalogo-proveedores`: el requirement de búsqueda del catálogo SHALL encontrar un proveedor por su RUT independientemente del formato del término (normalizando el RUT del término para la comparación).

## Impact

- **Backend**: `app/Http/Controllers/Maestros/ProveedorController.php` (`index()`). Los demás lugares que buscan proveedores por `rutproveedor` (`CasoPagoProveedorImporter`, `OrdenCompraMercadoPublicoService::verificarProveedor`) ya usan `Proveedor::normalizarRut()` y no requieren cambios.
- **Tests (Pest)**: cobertura de búsqueda por RUT con y sin puntos.
- **Sin cambios** en modelo de datos, migraciones, rutas, dependencias ni frontend.
