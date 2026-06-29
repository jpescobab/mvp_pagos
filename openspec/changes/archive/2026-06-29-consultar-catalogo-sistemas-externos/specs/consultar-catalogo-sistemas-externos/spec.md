## ADDED Requirements

### Requirement: Listar el catálogo de sistemas externos
El sistema SHALL exponer, a cualquier usuario autenticado, un listado de los `sistemas_externos` registrados con su código, nombre, tipo de integración, estado activo y cantidad de `trabajos_integracion` asociados.

#### Scenario: Listar el catálogo de sistemas externos
- **WHEN** un usuario autenticado visita el catálogo de sistemas externos
- **THEN** la respuesta incluye todos los `sistemas_externos` con su código, nombre, tipo de integración, si está activo y la cantidad de trabajos de integración asociados
