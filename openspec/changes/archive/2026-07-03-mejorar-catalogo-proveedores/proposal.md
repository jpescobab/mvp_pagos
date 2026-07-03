## Why

La tabla actual del catálogo de proveedores (`resources/js/pages/maestros/proveedores/index.tsx`) usa un espaciado amplio que deja pocas filas visibles por pantalla, y no tiene columna de acciones. El usuario compartió un mockup de referencia (tabla densa, avatar con iniciales, badges de estado, acciones en menú desplegable) y pidió explícitamente que la vista sea más eficiente en espacio y que las acciones se agrupen en un desplegable en vez de íconos sueltos.

## What Changes

- **Densidad**: reducir el padding vertical de filas/celdas y ajustar tipografía para que quepan más proveedores por pantalla sin sacrificar legibilidad.
- **Identidad visual por fila**: avatar circular con las iniciales del nombre del proveedor (reutilizando `useInitials`, mismo patrón que el avatar del topbar).
- **Badge de estado**: reemplazar el texto "Sí"/"No" de la columna `activo` por un badge con color semántico (éxito/peligro), sin inventar un tercer estado "Pendiente" que no existe en la base de datos.
- **Acciones en desplegable**: agregar una columna de acciones al final de cada fila con un menú desplegable (mismo patrón que `user-actions-menu.tsx`). Como el módulo sigue siendo de solo lectura, el menú incluye únicamente "Ver detalle" marcado como diferido ("Disponible próximamente"), igual al patrón ya usado en Usuarios — no se inventan acciones de editar/eliminar/restaurar que no existen.
- Se mantiene el buscador por RUT/nombre y la paginación existentes; no cambia el contrato de `ProveedorController::index()` ni de `ProveedorResource`.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `consulta-catalogo-proveedores`: se agrega un requirement sobre la presentación del listado (identidad visual por fila y menú de acciones desplegable), sin cambiar los campos ni el comportamiento de búsqueda/paginación ya especificados.

## Impact

- Frontend: `resources/js/pages/maestros/proveedores/index.tsx` (rediseño de la tabla), posible componente nuevo reutilizable para el badge de estado o el menú de acciones si se justifica por duplicación futura con otros catálogos de Maestros.
- Backend: sin cambios.
- Base de datos: sin cambios.
- Fuera de alcance: nuevos campos (giro, región, condición de pago), tercer estado "Pendiente", CRUD de proveedores (crear/editar/eliminar/restaurar), chips de filtro por estado con conteos.
