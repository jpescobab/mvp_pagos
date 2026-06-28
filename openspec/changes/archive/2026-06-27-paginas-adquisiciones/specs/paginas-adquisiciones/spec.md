## ADDED Requirements

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
