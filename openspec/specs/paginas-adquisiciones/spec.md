## Purpose

Esta capability cubre las páginas React/Inertia del dominio Adquisiciones: listado, detalle con acciones de workflow, y formulario de creación. Consumen exclusivamente la capa HTTP de `api-adquisiciones`; no introducen reglas de negocio ni requisitos documentales propios — el checklist, las transiciones disponibles y las validaciones son siempre los que entrega el backend.
## Requirements
### Requirement: Página de listado de procesos de adquisición
El sistema SHALL renderizar una página que muestre los procesos de adquisición paginados, con su código, nombre, modalidad, centro de costo, proveedor si existe, monto estimado y estado actual del workflow, sin filtros ni búsqueda no soportados por el backend.

#### Scenario: Listado con procesos
- **WHEN** un usuario autenticado visita la página de procesos de adquisición
- **THEN** la página muestra una fila por cada proceso recibido, con su código, nombre y un badge del estado actual del `Proceso`

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


### Requirement: El detalle del proceso muestra sus compras de Mercado Público vinculadas
La página de detalle de un proceso de adquisición SHALL mostrar dos secciones: las órdenes de compra de Mercado Público vinculadas y las licitaciones de Mercado Público vinculadas. Cada ítem SHALL mostrar sus datos clave (código, organismo comprador, estado en Mercado Público) y SHALL enlazar a su página de detalle. Cuando el proceso no tiene compras vinculadas de un tipo, la sección correspondiente SHALL mostrar un vacío explícito.

#### Scenario: Se muestran las órdenes de compra vinculadas con enlace a su detalle
- **WHEN** un usuario abre el detalle de un proceso con una orden de compra de Mercado Público vinculada
- **THEN** ve la sección de órdenes de compra con esa orden y sus datos clave
- **AND** puede navegar a su detalle desde ahí

#### Scenario: Secciones vacías cuando no hay compras vinculadas
- **WHEN** un usuario abre el detalle de un proceso sin órdenes de compra ni licitaciones vinculadas
- **THEN** ambas secciones muestran un mensaje de vacío explícito en lugar de una lista

### Requirement: El detalle permite descargar el PDF de la solicitud de compra
El sistema SHALL mostrar, en el detalle de un `proceso_adquisicion`, un punto de entrada para descargar su PDF, visible para cualquier usuario con permiso de consulta del proceso.

#### Scenario: Descargar el PDF desde el detalle
- **WHEN** un usuario con permiso de consulta abre el detalle de un proceso de adquisición y selecciona descargar el PDF
- **THEN** la aplicación descarga el documento PDF de esa solicitud
