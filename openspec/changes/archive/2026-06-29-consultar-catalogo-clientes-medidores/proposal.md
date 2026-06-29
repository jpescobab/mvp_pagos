## Why

`tablas-maestras-institucionales` ya modela `clientes_medidores` (número de cliente, proveedor, centro de costo, tipo de suministro, dirección) y `ClientesMedidoresSeeder` ya sembró 39 clientes eléctricos reales. Sin embargo, ningún controlador lo expone: hoy no hay forma de confirmar qué clientes/medidores eléctricos están registrados, a qué centro de costo pertenecen o cuál es su proveedor, salvo consultando la base de datos directamente. Es el mismo patrón ya resuelto para proveedores y sistemas externos.

## What Changes

- Exponer un catálogo de `clientes_medidores` con número de cliente, proveedor, centro de costo, tipo de suministro, dirección y si está activo.
- Abierto a cualquier usuario autenticado, sin permiso adicional — mismo nivel de acceso que el resto de catálogos de solo lectura ya expuestos.
- Sin página de detalle separada: todos los campos ya son visibles en la fila del listado.

## Capabilities

### New Capabilities
- `consultar-catalogo-clientes-medidores`: listar el catálogo de clientes/medidores eléctricos institucional.

## Impact

- Nuevos: `App\Http\Controllers\Maestros\ClienteMedidorController`, `App\Http\Resources\Maestros\ClienteMedidorResource`, página `resources/js/pages/maestros/clientes-medidores/index.tsx`.
- Modificados: `routes/maestros.php`, `resources/js/components/app-sidebar.tsx`.
- Sin cambios de esquema ni de permisos.
