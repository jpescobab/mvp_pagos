## MODIFIED Requirements

### Requirement: Página de vista previa de una OC nueva antes de guardar
El sistema SHALL renderizar una vista previa de la OC y sus ítems obtenidos de la API, mostrando si el proveedor emisor ya existe o será creado/completado automáticamente, con la acción de guardado siempre habilitada.

#### Scenario: Proveedor existente
- **WHEN** la vista previa de una OC nueva indica que el proveedor emisor ya existe
- **THEN** la página muestra el proveedor vinculado y la acción de guardado está habilitada

#### Scenario: Proveedor inexistente
- **WHEN** la vista previa de una OC nueva indica que el proveedor emisor no existe en el catálogo
- **THEN** la página indica que el proveedor se creará automáticamente al confirmar el guardado
- **AND** la acción de guardado está habilitada, sin ofrecer ni exigir un enlace a un formulario de alta manual de proveedor

#### Scenario: Confirmar guardado de una OC nueva
- **WHEN** el usuario confirma guardar la vista previa de una OC nueva
- **THEN** la página envía la confirmación al backend, muestra el resultado de la operación sobre el proveedor (creado, actualizado, o sin cambios), y navega al listado de Órdenes de Compra
