## MODIFIED Requirements

### Requirement: Crear un proceso de adquisición vía HTTP
El sistema SHALL exponer un formulario de creación que entregue los centros de costo, los funcionarios activos (con su unidad, para elegir el requirente) y los proveedores disponibles, y una ruta que cree un nuevo `proceso_adquisicion` delegando en `ProcesoAdquisicionService::crear()`. El formulario SHALL NOT ofrecer una elección manual de modalidad: la modalidad se deriva de la verificación de Convenio Marco enviada, y el código se genera automáticamente.

#### Scenario: Crear un proceso de adquisición con datos válidos
- **WHEN** un usuario autenticado envía los antecedentes generales requeridos (fecha de inicio, nombre, unidad requirente, funcionario requirente, características, motivo de contratación, verificación de Convenio Marco, monto estimado bajo 1.000 UTM)
- **THEN** se crea el `proceso_adquisicion` con su código generado automáticamente y su `Proceso` asociado en el estado inicial del workflow "adquisiciones"

#### Scenario: Rechazar la creación con un monto igual o mayor a 1.000 UTM
- **WHEN** un usuario envía un monto estimado igual o mayor al valor de 1.000 UTM vigente
- **THEN** el sistema rechaza la petición con un error de validación
- **AND** no se crea ningún `proceso_adquisicion`

#### Scenario: Formulario de creación incluye los datos disponibles
- **WHEN** un usuario autenticado solicita el formulario de creación de un proceso de adquisición
- **THEN** la respuesta incluye los centros de costo, los funcionarios activos y los proveedores existentes

### Requirement: Editar y actualizar un proceso de adquisición vía HTTP

El sistema SHALL exponer rutas autenticadas para editar y actualizar un `proceso_adquisicion`, gobernadas por el permiso `adquisiciones.editar_proceso` resuelto por `ProcesoAdquisicionPolicy::update`. La ruta de edición (`GET`) SHALL entregar el proceso con sus valores actuales más los centros de costo, los funcionarios activos y los proveedores disponibles. La ruta de actualización SHALL delegar íntegramente en `ProcesoAdquisicionService::actualizar()`, sin duplicar la validación de monto, la derivación de modalidad desde Convenio Marco, la sincronización del `Proceso` ni la regla de editabilidad por estado. Un usuario autenticado sin el permiso SHALL recibir una respuesta HTTP 403. `superadmin` conserva acceso vía `Gate::before`.

#### Scenario: Solicitar el formulario de edición con permiso

- **WHEN** un usuario con el permiso `adquisiciones.editar_proceso` solicita la edición de un proceso de adquisición
- **THEN** el sistema responde con una página Inertia que incluye los valores actuales del proceso, los centros de costo, los funcionarios activos y los proveedores

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

## ADDED Requirements

### Requirement: Descargar el PDF de un proceso de adquisición vía HTTP
El sistema SHALL exponer una ruta autenticada que entregue el PDF de un `proceso_adquisicion`, gobernada por el mismo permiso de consulta (`adquisiciones.consultar_proceso`) que el detalle del proceso.

#### Scenario: Descargar el PDF con permiso de consulta
- **WHEN** un usuario con el permiso `adquisiciones.consultar_proceso` solicita el PDF de un proceso de adquisición
- **THEN** el sistema responde con el archivo PDF de esa solicitud

#### Scenario: Rechazar la descarga sin permiso
- **WHEN** un usuario autenticado sin el permiso `adquisiciones.consultar_proceso` solicita el PDF de un proceso de adquisición
- **THEN** el sistema rechaza la petición con una respuesta HTTP 403
