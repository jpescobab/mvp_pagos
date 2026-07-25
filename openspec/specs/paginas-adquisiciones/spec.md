## Purpose

Esta capability cubre las páginas React/Inertia del dominio Adquisiciones: listado, detalle con acciones de workflow, y formulario de creación. Consumen exclusivamente la capa HTTP de `api-adquisiciones`; no introducen reglas de negocio ni requisitos documentales propios — el checklist, las transiciones disponibles y las validaciones son siempre los que entrega el backend.
## Requirements
### Requirement: Página de listado de procesos de adquisición
El sistema SHALL renderizar una página que muestre los procesos de adquisición paginados, con su código, modalidad, centro de costo, proveedor si existe, monto y estado actual del workflow, sin filtros ni búsqueda no soportados por el backend.

#### Scenario: Listado con procesos
- **WHEN** un usuario autenticado visita la página de procesos de adquisición
- **THEN** la página muestra una fila por cada proceso recibido, con su código, modalidad y un badge del estado actual del `Proceso`

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un proceso del listado
- **THEN** la aplicación navega a la página de detalle de ese proceso

### Requirement: Página de detalle de un proceso de adquisición con acciones de workflow
El sistema SHALL renderizar una página de detalle de un proceso de adquisición que muestre su estado actual, el checklist documental del proceso, el historial de transiciones, y permita ejecutar cualquiera de las transiciones disponibles delegando en el endpoint genérico ya existente.

#### Scenario: Ejecutar una transición sin comentario requerido
- **WHEN** un usuario con el permiso requerido selecciona una transición disponible que no requiere comentario
- **THEN** la página envía la transición al endpoint genérico y refleja el nuevo estado tras la respuesta

#### Scenario: Ejecutar una transición que requiere comentario
- **WHEN** un usuario selecciona una transición disponible marcada como `requiere_comentario`
- **THEN** la página solicita el comentario antes de enviar la transición

#### Scenario: Transición rechazada por el backend
- **WHEN** el backend rechaza una transición (sin permiso, código inválido, comentario faltante o documentos faltantes)
- **THEN** la página muestra el mensaje de error devuelto por el backend sin alterar el estado mostrado

#### Scenario: Checklist documental vacío
- **WHEN** el `Proceso` del proceso de adquisición no tiene checklist documental generado todavía
- **THEN** la página muestra un estado vacío explícito en lugar de asumir una estructura de datos

### Requirement: Formulario de creación de un proceso de adquisición
El sistema SHALL renderizar un formulario que permita elegir una modalidad activa, un centro de costo y opcionalmente un proveedor (todos recibidos del backend), indicar código/monto/objeto, y enviar la creación al endpoint ya existente.

#### Scenario: Crear un proceso con datos válidos
- **WHEN** un usuario autenticado completa código, modalidad, centro de costo y objeto, y envía el formulario
- **THEN** el formulario envía los datos al endpoint de creación
- **AND** tras la respuesta exitosa la aplicación navega al detalle del proceso creado

#### Scenario: Envío rechazado por el backend
- **WHEN** el backend rechaza la creación (validación o modalidad inválida)
- **THEN** el formulario muestra los errores de validación devueltos sin perder los valores ya ingresados

### Requirement: Formulario de edición de un proceso de adquisición

El sistema SHALL renderizar una página de edición que permita modificar la modalidad activa, el centro de costo, el proveedor opcional, el código, el monto y el objeto de un `proceso_adquisicion` (todos precargados con los valores actuales recibidos del backend) y enviar la actualización al endpoint existente. La página SHALL consumir exclusivamente la capa HTTP de `api-adquisiciones`, sin introducir reglas de negocio propias.

#### Scenario: Editar un proceso con datos válidos

- **WHEN** un usuario modifica uno o más campos del proceso y envía el formulario
- **THEN** el formulario envía los datos al endpoint de actualización
- **AND** tras la respuesta exitosa la aplicación navega al detalle del proceso

#### Scenario: Envío de edición rechazado por el backend

- **WHEN** el backend rechaza la actualización (validación, modalidad inválida o proceso no editable)
- **THEN** el formulario muestra los errores devueltos sin perder los valores ya ingresados

### Requirement: El detalle ofrece editar solo cuando el proceso es editable

El sistema SHALL mostrar el punto de entrada de edición (por ejemplo, un botón "Editar") en el detalle de un `proceso_adquisicion` únicamente cuando el proceso esté en estado `borrador` y el usuario tenga el permiso `adquisiciones.editar_proceso`. En cualquier otro caso el punto de entrada SHALL estar ausente.

#### Scenario: Botón de edición visible en borrador con permiso

- **WHEN** un usuario con el permiso `adquisiciones.editar_proceso` abre el detalle de un proceso en estado `borrador`
- **THEN** la página muestra el punto de entrada para editar el proceso

#### Scenario: Botón de edición ausente fuera de borrador

- **WHEN** un usuario abre el detalle de un proceso cuyo estado no es `borrador`
- **THEN** la página no muestra el punto de entrada de edición

#### Scenario: Botón de edición ausente sin permiso

- **WHEN** un usuario sin el permiso `adquisiciones.editar_proceso` abre el detalle de un proceso en estado `borrador`
- **THEN** la página no muestra el punto de entrada de edición

