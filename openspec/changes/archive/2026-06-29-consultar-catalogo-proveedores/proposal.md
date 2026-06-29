## Why

`tablas-maestras-institucionales` ya modela `proveedores` con `rutproveedor` como identificador institucional único, y `ProveedoresSeeder` ya sembró 977 proveedores reales (RUT, nombre, correo, dirección, contacto). Sin embargo, ningún controlador expone ese catálogo: hoy la única forma de "buscar un proveedor" es la búsqueda acotada e indirecta de `BuscarProcesoAdquisicionController` (que busca procesos de adquisición, no proveedores) o consultar la base de datos directamente. No existe ninguna forma de confirmar el RUT, correo o dirección registrada de un proveedor antes de vincularlo a un caso o documento.

## What Changes

- Exponer un catálogo de `proveedores` con búsqueda por RUT o nombre, paginado.
- Cada proveedor muestra su RUT, nombre, correo, dirección, contacto y si está activo.
- Es de solo lectura, abierto a cualquier usuario autenticado — mismo dato (RUT y nombre del proveedor) que ya es visible sin restricción adicional en el detalle de cualquier caso de pago.
- Sin página de detalle separada: todos los campos del proveedor ya son visibles en la fila del listado; una página de detalle solo duplicaría esa misma información sin agregar datos relacionados (la relación a `clientes_medidores` pertenece al módulo Consumo Eléctrico, todavía no construido).

## Capabilities

### New Capabilities
- `consulta-catalogo-proveedores`: buscar y listar el catálogo de proveedores institucional.

## Impact

- Nuevos: `App\Http\Controllers\Maestros\ProveedorController`, `App\Http\Resources\Maestros\ProveedorResource`, `routes/maestros.php`, página `resources/js/pages/maestros/proveedores/index.tsx`.
- Modificados: `routes/web.php` (require del nuevo archivo), `resources/js/components/app-sidebar.tsx` (nuevo ítem de navegación).
- Sin cambios de esquema ni de permisos.
