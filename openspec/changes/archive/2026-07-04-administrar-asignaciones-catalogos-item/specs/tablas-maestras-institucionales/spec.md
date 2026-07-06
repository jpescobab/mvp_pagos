## MODIFIED Requirements

### Requirement: Modelar el clasificador presupuestario institucional
El sistema SHALL modelar `items`, `asignaciones` y `catalogos` como el clasificador presupuestario institucional, donde `asignaciones` y `catalogos` pertenecen directamente a un `item`. El sistema SHALL permitir crear, editar y eliminar asignaciones y catálogos desde el detalle de su ítem padre, sin exponer un listado independiente de todas las asignaciones o catálogos del sistema.

#### Scenario: Registrar una asignación bajo su ítem
- **WHEN** se registra una asignación presupuestaria
- **THEN** queda asociada a su ítem mediante `item_id`
- **AND** su `codigo` es único

#### Scenario: Registrar un catálogo bajo su ítem
- **WHEN** se registra un catálogo (cuenta presupuestaria utilizable)
- **THEN** queda asociado a su ítem mediante `item_id`
- **AND** su `codigo` es único
- **AND** su disponibilidad para uso se controla con el campo `activo`

#### Scenario: Administrar asignaciones y catálogos desde el detalle del ítem
- **WHEN** un usuario con permiso `core_institucional.administrar` visita el detalle de un ítem presupuestario
- **THEN** el sistema muestra sus asignaciones y catálogos asociados, con acciones para crear, editar y eliminar cada uno
- **AND** no existe un listado independiente que muestre asignaciones o catálogos de todos los ítems a la vez

#### Scenario: Editar una asignación o catálogo
- **WHEN** un usuario con permiso `core_institucional.administrar` edita una asignación o catálogo existente
- **THEN** el sistema actualiza `codigo`, `nombre`, `descripcion` y `activo`
- **AND** rechaza el cambio si el nuevo `codigo` colisiona con el de otra asignación o catálogo

#### Scenario: Eliminar una asignación o catálogo
- **WHEN** un usuario con permiso `core_institucional.administrar` elimina una asignación o catálogo
- **THEN** el sistema lo elimina (soft delete)

#### Scenario: Usuario sin permiso no puede administrar asignaciones ni catálogos
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta crear, editar o eliminar una asignación o catálogo
- **THEN** el sistema rechaza la acción
