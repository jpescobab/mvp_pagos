## ADDED Requirements

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
