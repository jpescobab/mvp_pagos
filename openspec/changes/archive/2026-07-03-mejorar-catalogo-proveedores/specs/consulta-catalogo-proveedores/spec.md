## ADDED Requirements

### Requirement: Presentación densa del listado de proveedores
El sistema SHALL presentar el listado del catálogo de proveedores en una tabla compacta donde cada fila muestra un avatar con las iniciales del nombre del proveedor, un badge de estado con color semántico (activo/inactivo) y un menú de acciones desplegable al final de la fila, sin reducir los campos ya expuestos por el requirement de búsqueda y listado.

#### Scenario: Avatar con iniciales por fila
- **WHEN** un usuario autenticado visualiza el catálogo de proveedores
- **THEN** cada fila muestra un avatar con las iniciales del nombre del proveedor

#### Scenario: Badge de estado con color semántico
- **WHEN** un usuario autenticado visualiza el catálogo de proveedores
- **THEN** el estado de cada proveedor se muestra como un badge (activo o inactivo) con color semántico, no como texto plano "Sí"/"No"

#### Scenario: Menú de acciones desplegable
- **WHEN** un usuario autenticado abre el menú de acciones de una fila del catálogo de proveedores
- **THEN** el menú muestra las acciones disponibles agrupadas en un desplegable en vez de íconos sueltos en la fila
- **AND** las acciones que aún no están implementadas se muestran deshabilitadas con la indicación "Disponible próximamente"
