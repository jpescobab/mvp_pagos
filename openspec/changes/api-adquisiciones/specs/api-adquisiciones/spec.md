## ADDED Requirements

### Requirement: Listar y ver procesos de adquisición vía HTTP
El sistema SHALL exponer rutas autenticadas para listar `procesos_adquisicion` y ver el detalle de uno, incluyendo el estado actual, el historial de transiciones y el checklist documental de su `Proceso` asociado.

#### Scenario: Listar procesos de adquisición
- **WHEN** un usuario autenticado solicita la lista de procesos de adquisición
- **THEN** el sistema responde con una página Inertia que incluye los procesos paginados

#### Scenario: Ver el detalle de un proceso de adquisición
- **WHEN** un usuario autenticado solicita el detalle de un proceso de adquisición
- **THEN** el sistema responde con una página Inertia que incluye el proceso, su `Proceso` de workflow, estado actual e historial de transiciones

#### Scenario: Ver el checklist documental del proceso
- **WHEN** un usuario autenticado solicita el detalle de un proceso de adquisición cuyo `Proceso` tiene un `ChecklistDocumentalProceso` generado
- **THEN** la respuesta incluye los items del checklist (tipo de documento, tipo de requisito, estado de cumplimiento)

#### Scenario: Proceso sin checklist generado
- **WHEN** un usuario autenticado solicita el detalle de un proceso de adquisición cuyo `Proceso` no tiene `ChecklistDocumentalProceso` generado todavía
- **THEN** la respuesta refleja la ausencia de checklist sin error

### Requirement: Ejecutar transiciones de workflow vía un endpoint genérico
El sistema SHALL exponer un único endpoint HTTP que reciba el código de una transición y lo delegue íntegramente a `TransicionWorkflowService::execute()`, sin duplicar su lógica de autorización, comentario requerido ni documentos obligatorios.

#### Scenario: Ejecutar una transición válida
- **WHEN** un usuario con el permiso requerido envía un código de transición válido para el estado actual de un proceso de adquisición
- **THEN** el `Proceso` del proceso de adquisición transiciona al estado destino
- **AND** la respuesta refleja el nuevo estado

#### Scenario: Rechazar una transición sin permiso o inválida
- **WHEN** un usuario sin el permiso requerido, o con un código de transición no válido para el estado actual, intenta ejecutar una transición
- **THEN** el sistema rechaza la petición sin modificar el estado del `Proceso`
- **AND** la excepción de `TransicionWorkflowService` se traduce a una respuesta HTTP de error apropiada

### Requirement: Crear un proceso de adquisición vía HTTP
El sistema SHALL exponer un formulario de creación que entregue las modalidades activas, los centros de costo y los proveedores disponibles, y una ruta que cree un nuevo `proceso_adquisicion` delegando en `ProcesoAdquisicionService::crear()`.

#### Scenario: Crear un proceso de adquisición con datos válidos
- **WHEN** un usuario autenticado envía los datos requeridos (código, modalidad, centro de costo, objeto) referenciando una modalidad activa
- **THEN** se crea el `proceso_adquisicion` y su `Proceso` asociado en el estado inicial del workflow "adquisiciones"

#### Scenario: Rechazar la creación con una modalidad inválida
- **WHEN** un usuario envía una modalidad inexistente o inactiva
- **THEN** el sistema rechaza la petición con un error de validación
- **AND** no se crea ningún `proceso_adquisicion`

#### Scenario: Formulario de creación incluye los datos disponibles
- **WHEN** un usuario autenticado solicita el formulario de creación de un proceso de adquisición
- **THEN** la respuesta incluye las modalidades activas, los centros de costo y los proveedores existentes
