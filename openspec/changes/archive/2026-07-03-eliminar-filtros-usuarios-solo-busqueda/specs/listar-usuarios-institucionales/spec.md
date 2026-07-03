## REMOVED Requirements

### Requirement: Buscar y filtrar usuarios institucionales
**Reason**: Se elimina la capacidad de filtrar el listado de usuarios por estado, rol, jurisdicción, centro financiero y centro de costo, por decisión de producto para simplificar la pantalla — el índice conserva únicamente la búsqueda por texto.
**Migration**: La búsqueda por nombre, email y rut se conserva sin cambios bajo el nuevo requirement "Buscar usuarios institucionales". No hay ruta de reemplazo para los filtros eliminados; si se necesitan a futuro, se proponen como un change nuevo.

## ADDED Requirements

### Requirement: Buscar usuarios institucionales
El sistema SHALL permitir buscar usuarios por nombre, email y rut (del `Funcionario` vinculado), conservando el término de búsqueda aplicado tras cualquier acción sobre el listado. El sistema SHALL NOT ofrecer filtros adicionales por estado, rol, jurisdicción, centro financiero o centro de costo en este listado.

#### Scenario: Búsqueda general
- **WHEN** se envía un término de búsqueda
- **THEN** el sistema retorna solo los usuarios cuyo nombre, email o rut coincidan parcialmente con el término

#### Scenario: Sin resultados de búsqueda
- **WHEN** el término de búsqueda no coincide con ningún usuario
- **THEN** la página muestra el mensaje "No se encontraron usuarios con esa búsqueda."
