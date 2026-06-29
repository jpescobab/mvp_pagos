## ADDED Requirements

### Requirement: Buscar y listar el catálogo de proveedores
El sistema SHALL exponer, a cualquier usuario autenticado, un listado paginado de `proveedores` con su RUT, nombre, correo, dirección, contacto y si está activo, filtrable por coincidencia de texto en RUT o nombre.

#### Scenario: Listar el catálogo sin filtro
- **WHEN** un usuario autenticado visita el catálogo de proveedores sin término de búsqueda
- **THEN** la respuesta incluye los `proveedores` paginados con su RUT, nombre, correo, dirección, contacto y si está activo

#### Scenario: Buscar por RUT o nombre
- **WHEN** un usuario autenticado busca un término en el catálogo de proveedores
- **THEN** la respuesta incluye únicamente los `proveedores` cuyo `rutproveedor` o `nombre` contienen ese término
