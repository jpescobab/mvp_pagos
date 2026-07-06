## MODIFIED Requirements

### Requirement: Listar el catálogo de clientes medidores
El sistema SHALL exponer, a los usuarios con el permiso `core_institucional.administrar`, un listado paginado de los `clientes_medidores` registrados con su número de cliente, proveedor, centro de costo, tipo de suministro, dirección y si está activo, con búsqueda por número de cliente, nombre del proveedor o código/nombre del centro de costo.

#### Scenario: Listar el catálogo de clientes medidores
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el catálogo de clientes medidores sin filtro
- **THEN** la respuesta incluye una página de `clientes_medidores` con su número de cliente, proveedor, centro de costo, tipo de suministro, dirección y si está activo

#### Scenario: Buscar por número de cliente, proveedor o centro de costo
- **WHEN** un usuario con el permiso `core_institucional.administrar` ingresa un término de búsqueda en el catálogo de clientes medidores
- **THEN** el sistema filtra los resultados por coincidencia parcial en el número de cliente, el nombre del proveedor asociado o el código/nombre del centro de costo asociado

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta visitar el catálogo de clientes medidores
- **THEN** el sistema rechaza la solicitud
