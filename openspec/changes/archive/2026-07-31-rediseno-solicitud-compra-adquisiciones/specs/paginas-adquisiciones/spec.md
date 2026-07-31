## MODIFIED Requirements

### Requirement: Página de listado de procesos de adquisición
El sistema SHALL renderizar una página que muestre los procesos de adquisición paginados, con su código, nombre, modalidad, centro de costo, proveedor si existe, monto estimado y estado actual del workflow, sin filtros ni búsqueda no soportados por el backend.

#### Scenario: Listado con procesos
- **WHEN** un usuario autenticado visita la página de procesos de adquisición
- **THEN** la página muestra una fila por cada proceso recibido, con su código, nombre y un badge del estado actual del `Proceso`

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un proceso del listado
- **THEN** la aplicación navega a la página de detalle de ese proceso

### Requirement: Formulario de creación de un proceso de adquisición
El sistema SHALL renderizar un formulario de solicitud de compra organizado en pasos (Identificación, Requerimientos, Moneda y Montos) con los antecedentes generales de la compra (fecha de inicio, nombre, ID de requerimiento opcional, unidad requirente, funcionario requirente, características del bien o servicio, motivo de contratación, código BIP opcional), la verificación de si la compra está en el Plan Anual de Compras (revelando el ID del PAC solo cuando corresponda), la verificación de Convenio Marco, y la moneda/monto estimado (con paridad cuando la moneda no es CLP), y enviar la creación al endpoint existente. El formulario SHALL NOT incluir un campo de código (se genera automáticamente) ni un campo de selección manual de modalidad. El selector de funcionario requirente SHALL filtrarse según la unidad requirente elegida. El formulario SHALL validar los campos requeridos en el cliente antes de enviar la solicitud al backend.

#### Scenario: Crear un proceso con datos válidos
- **WHEN** un usuario autenticado completa los antecedentes generales requeridos, elige un funcionario requirente de la unidad seleccionada, responde la verificación de Convenio Marco y envía el formulario
- **THEN** el formulario envía los datos al endpoint de creación
- **AND** tras la respuesta exitosa la aplicación navega al detalle del proceso creado

#### Scenario: Navegar entre los pasos del formulario
- **WHEN** un usuario completa un paso del formulario y avanza al siguiente
- **THEN** el paso queda marcado como completo
- **AND** el usuario puede volver a un paso anterior sin perder los datos ya ingresados

#### Scenario: El selector de funcionario requirente se filtra por unidad
- **WHEN** un usuario elige una unidad requirente en el formulario
- **THEN** el selector de funcionario requirente solo ofrece funcionarios de esa unidad

#### Scenario: El ID del PAC solo aparece si la compra está en el Plan de Compras
- **WHEN** un usuario responde "No" a si la compra está en el Plan Anual de Compras
- **THEN** el formulario no muestra el campo ID del PAC

#### Scenario: Envío bloqueado por validación en el cliente
- **WHEN** un usuario intenta enviar el formulario con un campo requerido vacío
- **THEN** el formulario bloquea el envío y muestra el error localmente, sin llamar al backend

#### Scenario: Envío rechazado por el backend
- **WHEN** el backend rechaza la creación (validación, o monto igual o mayor a 1.000 UTM)
- **THEN** el formulario muestra los errores de validación devueltos sin perder los valores ya ingresados

### Requirement: Formulario de edición de un proceso de adquisición

El sistema SHALL renderizar una página de edición que permita modificar los antecedentes generales, la unidad requirente, el funcionario requirente, el proveedor opcional, el monto estimado y la verificación de Convenio Marco de un `proceso_adquisicion` (todos precargados con los valores actuales recibidos del backend) y enviar la actualización al endpoint existente. La página SHALL consumir exclusivamente la capa HTTP de `api-adquisiciones`, sin introducir reglas de negocio propias.

#### Scenario: Editar un proceso con datos válidos

- **WHEN** un usuario modifica uno o más campos del proceso y envía el formulario
- **THEN** el formulario envía los datos al endpoint de actualización
- **AND** tras la respuesta exitosa la aplicación navega al detalle del proceso

#### Scenario: Envío de edición rechazado por el backend

- **WHEN** el backend rechaza la actualización (validación, umbral de monto o proceso no editable)
- **THEN** el formulario muestra los errores devueltos sin perder los valores ya ingresados

## ADDED Requirements

### Requirement: El detalle permite descargar el PDF de la solicitud de compra
El sistema SHALL mostrar, en el detalle de un `proceso_adquisicion`, un punto de entrada para descargar su PDF, visible para cualquier usuario con permiso de consulta del proceso.

#### Scenario: Descargar el PDF desde el detalle
- **WHEN** un usuario con permiso de consulta abre el detalle de un proceso de adquisición y selecciona descargar el PDF
- **THEN** la aplicación descarga el documento PDF de esa solicitud
