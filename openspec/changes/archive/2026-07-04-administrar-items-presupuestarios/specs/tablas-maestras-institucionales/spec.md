## MODIFIED Requirements

### Requirement: Modelar el clasificador presupuestario institucional
El sistema SHALL modelar `items`, `asignaciones` y `catalogos` como el clasificador presupuestario institucional, donde `asignaciones` y `catalogos` pertenecen directamente a un `item`. El sistema SHALL exponer un CRUD administrable (HTTP + UI) sobre `items`, accesible desde el grupo "Administración", gateado por el mismo permiso que el resto de catálogos de tablas maestras.

#### Scenario: Registrar una asignación bajo su ítem
- **WHEN** se registra una asignación presupuestaria
- **THEN** queda asociada a su ítem mediante `item_id`
- **AND** su `codigo` es único

#### Scenario: Registrar un catálogo bajo su ítem
- **WHEN** se registra un catálogo (cuenta presupuestaria utilizable)
- **THEN** queda asociado a su ítem mediante `item_id`
- **AND** su `codigo` es único
- **AND** su disponibilidad para uso se controla con el campo `activo`

#### Scenario: Listar ítems presupuestarios
- **WHEN** un usuario autenticado visita el listado de ítems presupuestarios
- **THEN** el sistema muestra los ítems paginados, con búsqueda por `codigo`/`nombre`
- **AND** cada fila muestra su estado (`activo`/`inactivo`) con un badge de estado

#### Scenario: Crear un ítem presupuestario
- **WHEN** un usuario con permiso `core_institucional.administrar` registra un nuevo ítem con `codigo` y `nombre`
- **THEN** el sistema lo crea con `activo = true` por defecto
- **AND** rechaza el alta si el `codigo` ya existe

#### Scenario: Editar un ítem presupuestario
- **WHEN** un usuario con permiso `core_institucional.administrar` edita un ítem existente
- **THEN** el sistema actualiza `codigo`, `nombre`, `descripcion` y `activo`
- **AND** rechaza el cambio si el nuevo `codigo` colisiona con el de otro ítem

#### Scenario: Eliminar un ítem presupuestario sin asignaciones ni catálogos
- **WHEN** un usuario con permiso `core_institucional.administrar` elimina un ítem que no tiene asignaciones ni catálogos asociados
- **THEN** el sistema lo elimina (soft delete)

#### Scenario: Bloquear la eliminación de un ítem con asignaciones o catálogos
- **WHEN** un usuario intenta eliminar un ítem que tiene al menos una asignación o catálogo asociado
- **THEN** el sistema rechaza la eliminación y explica el motivo

#### Scenario: Usuario sin permiso no puede administrar ítems
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta crear, editar o eliminar un ítem
- **THEN** el sistema rechaza la acción
