# Spec: consulta-catalogo-proveedores

## Purpose

Exponer, de solo lectura, el catálogo institucional de `proveedores` ya sembrado, con búsqueda por RUT o nombre, para que sea consultable antes de vincularlo a un caso o documento.

## Requirements

### Requirement: Buscar y listar el catálogo de proveedores
El sistema SHALL exponer, a los usuarios con el permiso `core_institucional.administrar`, un listado paginado de `proveedores` con su RUT, nombre, correo, dirección, contacto y si está activo, filtrable por coincidencia de texto en RUT o nombre.

#### Scenario: Listar el catálogo sin filtro
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el catálogo de proveedores sin término de búsqueda
- **THEN** la respuesta incluye los `proveedores` paginados con su RUT, nombre, correo, dirección, contacto y si está activo

#### Scenario: Buscar por RUT o nombre
- **WHEN** un usuario con el permiso `core_institucional.administrar` busca un término en el catálogo de proveedores
- **THEN** la respuesta incluye únicamente los `proveedores` cuyo `rutproveedor` o `nombre` contienen ese término

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta visitar el catálogo de proveedores
- **THEN** el sistema rechaza la solicitud

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
