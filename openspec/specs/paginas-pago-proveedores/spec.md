## Purpose

Esta capability cubre las páginas React/Inertia del dominio de pago de proveedores: listado y detalle de casos de pago de proveedores, y listado/creación de egresos CGU, consumiendo la capa HTTP de `api-pago-proveedores`.

## Requirements

### Requirement: Página de listado de casos de pago de proveedores
El sistema SHALL renderizar una página que muestre los casos de pago de proveedores paginados, con su proveedor, monto, estado SGF y estado actual del workflow, sin filtros ni búsqueda no soportados por el backend.

#### Scenario: Listado con casos
- **WHEN** un usuario autenticado visita la página de casos de pago de proveedores
- **THEN** la página muestra una fila por cada caso recibido, con el proveedor, monto y un badge del estado actual del `Proceso`

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un caso del listado
- **THEN** la aplicación navega a la página de detalle de ese caso

### Requirement: Página de detalle de un caso con acciones de workflow
El sistema SHALL renderizar una página de detalle de un caso que muestre su estado actual, el checklist documental del proceso, el historial de transiciones, y permita ejecutar cualquiera de las transiciones disponibles delegando en el endpoint genérico ya existente.

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
- **WHEN** el `Proceso` del caso no tiene checklist documental generado todavía
- **THEN** la página muestra un estado vacío explícito en lugar de asumir una estructura de datos

### Requirement: Página de listado de egresos CGU
El sistema SHALL renderizar una página que muestre los egresos CGU paginados junto con los casos de pago de proveedores que cada uno cubre.

#### Scenario: Listado con egresos
- **WHEN** un usuario autenticado visita la página de egresos CGU
- **THEN** la página muestra una fila por cada egreso, con su número, fecha, monto total y los `sgf_id` de los casos que cubre

### Requirement: Formulario de creación de egreso CGU
El sistema SHALL renderizar un formulario que permita elegir uno o más `casos_pago_proveedor` existentes, asignar un monto a cada uno, y enviar la creación del egreso CGU al endpoint ya existente.

#### Scenario: Crear un egreso cubriendo varios casos
- **WHEN** un usuario con permiso `pago_proveedores.registrar_egreso` selecciona dos o más casos, asigna un monto a cada uno y envía el formulario
- **THEN** el formulario envía `casos` como un arreglo de `{caso_pago_proveedor_id, monto}` al endpoint de creación

#### Scenario: Envío rechazado por el backend
- **WHEN** el backend rechaza la creación (validación o permiso)
- **THEN** el formulario muestra los errores de validación devueltos sin perder los valores ya ingresados
