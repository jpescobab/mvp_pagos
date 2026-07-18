## MODIFIED Requirements

### Requirement: Buscar y listar el catálogo de proveedores
El sistema SHALL exponer, a los usuarios con el permiso `core_institucional.administrar`, un listado paginado de `proveedores` con su RUT, nombre, correo, dirección, contacto y si está activo, filtrable por coincidencia de texto en RUT o nombre. La búsqueda por RUT SHALL ser independiente del formato del término: un RUT escrito con puntos y guión SHALL encontrar al proveedor aunque su `rutproveedor` esté almacenado normalizado (sin puntos), comparando también el término normalizado con la misma regla de normalización usada al guardar.

#### Scenario: Listar el catálogo sin filtro
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el catálogo de proveedores sin término de búsqueda
- **THEN** la respuesta incluye los `proveedores` paginados con su RUT, nombre, correo, dirección, contacto y si está activo

#### Scenario: Buscar por RUT o nombre
- **WHEN** un usuario con el permiso `core_institucional.administrar` busca un término en el catálogo de proveedores
- **THEN** la respuesta incluye únicamente los `proveedores` cuyo `rutproveedor` o `nombre` contienen ese término

#### Scenario: Buscar por RUT con puntos encuentra al proveedor almacenado normalizado
- **WHEN** un usuario busca un RUT con su formato natural (con puntos y guión, p. ej. `77.634.019-7`) y existe un proveedor cuyo `rutproveedor` está almacenado normalizado sin puntos (`77634019-7`)
- **THEN** la respuesta incluye a ese proveedor

#### Scenario: Buscar un nombre no se ve afectado por la normalización de RUT
- **WHEN** un usuario busca un término de texto que no corresponde a un RUT (sin dígitos ni dígito verificador)
- **THEN** la respuesta incluye únicamente los `proveedores` cuyo `nombre` o `rutproveedor` contienen ese término, sin traer todo el catálogo

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta visitar el catálogo de proveedores
- **THEN** el sistema rechaza la solicitud
