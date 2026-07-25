## Purpose

Esta capability es la capa de presentación HTTP/Inertia sobre la capability de dominio `adquisiciones`. Traduce peticiones HTTP autenticadas a llamadas a `ProcesoAdquisicionService` y `TransicionWorkflowService::execute()`, sin introducir reglas de negocio nuevas: autorización, validación de transición, documentos obligatorios y comentario requerido siguen gobernados íntegramente por esos servicios.
## Requirements
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

### Requirement: El acceso a los procesos de adquisición vía HTTP se controla por permiso

El sistema SHALL restringir el listado, la visualización y la creación de `procesos_adquisicion` vía HTTP a usuarios que posean el permiso correspondiente, resuelto por `ProcesoAdquisicionPolicy`. Listar y ver el detalle SHALL exigir el permiso `adquisiciones.consultar_proceso`; crear (formulario y guardado) SHALL exigir el permiso `adquisiciones.crear_proceso`. Un usuario autenticado sin el permiso requerido SHALL recibir una respuesta HTTP 403. El rol `superadmin` conserva acceso total vía `Gate::before`, sin necesidad de estos permisos.

#### Scenario: Listar procesos con el permiso de consulta

- **WHEN** un usuario autenticado con el permiso `adquisiciones.consultar_proceso` solicita la lista de procesos de adquisición
- **THEN** el sistema responde con la página Inertia del listado paginado

#### Scenario: Ver el detalle de un proceso con el permiso de consulta

- **WHEN** un usuario autenticado con el permiso `adquisiciones.consultar_proceso` solicita el detalle de un proceso de adquisición
- **THEN** el sistema responde con la página Inertia del detalle

#### Scenario: Rechazar el listado o el detalle sin el permiso de consulta

- **WHEN** un usuario autenticado sin el permiso `adquisiciones.consultar_proceso` solicita el listado o el detalle de un proceso de adquisición
- **THEN** el sistema rechaza la petición con una respuesta HTTP 403

#### Scenario: Crear un proceso con el permiso de creación

- **WHEN** un usuario autenticado con el permiso `adquisiciones.crear_proceso` solicita el formulario de creación o envía datos válidos para crear un proceso de adquisición
- **THEN** el sistema muestra el formulario o crea el `proceso_adquisicion` según corresponda

#### Scenario: Rechazar la creación sin el permiso de creación

- **WHEN** un usuario autenticado sin el permiso `adquisiciones.crear_proceso` solicita el formulario de creación o intenta guardar un proceso de adquisición
- **THEN** el sistema rechaza la petición con una respuesta HTTP 403
- **AND** no se crea ningún `proceso_adquisicion`

#### Scenario: superadmin accede sin los permisos explícitos

- **WHEN** un usuario con el rol `superadmin` lista, ve o crea un proceso de adquisición sin tener asignados `adquisiciones.consultar_proceso` ni `adquisiciones.crear_proceso`
- **THEN** el sistema permite la operación por la regla `Gate::before`

### Requirement: Editar y actualizar un proceso de adquisición vía HTTP

El sistema SHALL exponer rutas autenticadas para editar y actualizar un `proceso_adquisicion`, gobernadas por el permiso `adquisiciones.editar_proceso` resuelto por `ProcesoAdquisicionPolicy::update`. La ruta de edición (`GET`) SHALL entregar el proceso con sus valores actuales más las modalidades activas, los centros de costo y los proveedores disponibles. La ruta de actualización SHALL delegar íntegramente en `ProcesoAdquisicionService::actualizar()`, sin duplicar la validación de modalidad, la sincronización del `Proceso` ni la regla de editabilidad por estado. Un usuario autenticado sin el permiso SHALL recibir una respuesta HTTP 403. `superadmin` conserva acceso vía `Gate::before`.

#### Scenario: Solicitar el formulario de edición con permiso

- **WHEN** un usuario con el permiso `adquisiciones.editar_proceso` solicita la edición de un proceso de adquisición
- **THEN** el sistema responde con una página Inertia que incluye los valores actuales del proceso, las modalidades activas, los centros de costo y los proveedores

#### Scenario: Actualizar un proceso en borrador con datos válidos

- **WHEN** un usuario con el permiso `adquisiciones.editar_proceso` envía datos válidos para actualizar un proceso de adquisición que está en estado `borrador`
- **THEN** el sistema actualiza el proceso y su `Proceso` asociado
- **AND** redirige al detalle del proceso

#### Scenario: Rechazar la edición o actualización sin permiso

- **WHEN** un usuario autenticado sin el permiso `adquisiciones.editar_proceso` solicita editar o actualizar un proceso de adquisición
- **THEN** el sistema rechaza la petición con una respuesta HTTP 403
- **AND** no modifica el proceso

#### Scenario: Rechazar la actualización de un proceso fuera de borrador

- **WHEN** un usuario con el permiso intenta actualizar un proceso de adquisición cuyo estado ya no es `borrador`
- **THEN** el sistema traduce la excepción de dominio a una respuesta HTTP de error apropiada
- **AND** no modifica el proceso

#### Scenario: El código permanece único ignorando el propio registro

- **WHEN** un usuario actualiza un proceso de adquisición sin cambiar su `codigo`
- **THEN** la validación de unicidad de `codigo` no se dispara contra el propio registro y la actualización se acepta

