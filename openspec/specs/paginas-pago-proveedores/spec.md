## Purpose

Esta capability cubre las páginas React/Inertia del dominio de pago de proveedores: listado y detalle de casos de pago de proveedores, y listado/creación de egresos CGU, consumiendo la capa HTTP de `api-pago-proveedores`.

## Requirements

### Requirement: Página de listado de casos de pago de proveedores
El sistema SHALL renderizar una página que muestre los casos de pago de proveedores paginados, con id (`sgf_id`), periodo, observación, observación de egreso, folio de egreso, RUT y nombre del proveedor, número, fecha SII, monto, estado SGF y estado actual del workflow, sin filtros ni búsqueda no soportados por el backend. Los campos de referencia SGF que no estén disponibles para un caso SHALL mostrarse con un fallback explícito en vez de una celda vacía.

#### Scenario: Listado con casos
- **WHEN** un usuario autenticado visita la página de casos de pago de proveedores
- **THEN** la página muestra una fila por cada caso recibido, con su `sgf_id`, periodo, observación, observación de egreso, folio de egreso, RUT y nombre del proveedor, número, fecha SII, monto y un badge del estado actual del `Proceso`

#### Scenario: Campo de referencia SGF no disponible
- **WHEN** un caso listado tiene `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero` o `fecha_sii` en `null`
- **THEN** la columna correspondiente muestra `"—"` en vez de una celda vacía

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un caso del listado
- **THEN** la aplicación navega a la página de detalle de ese caso

### Requirement: Página de detalle de un caso con acciones de workflow
El sistema SHALL renderizar una página de detalle de un caso que muestre su estado actual, el checklist documental del proceso, el historial de transiciones, y permita ejecutar las transiciones disponibles delegando en el endpoint genérico ya existente, salvo las transiciones gobernadas por la revisión de pagos en dos instancias (`observar_finanzas`, `aprobar_finanzas`, `rechazar_finanzas`, `devolver_a_finanzas`, `aprobar_zonal`, `rechazar_zonal`), que el sistema SHALL rechazar desde este endpoint y que solo se ejecutan desde Revisión de Pagos.

#### Scenario: Ejecutar una transición sin comentario requerido
- **WHEN** un usuario con el permiso requerido selecciona una transición disponible que no requiere comentario
- **THEN** la página envía la transición al endpoint genérico y refleja el nuevo estado tras la respuesta

#### Scenario: Ejecutar una transición que requiere comentario
- **WHEN** un usuario selecciona una transición disponible marcada como `requiere_comentario`
- **THEN** la página solicita el comentario antes de enviar la transición

#### Scenario: Transición rechazada por el backend
- **WHEN** el backend rechaza una transición (sin permiso, código inválido, comentario faltante o documentos faltantes)
- **THEN** la página muestra el mensaje de error devuelto por el backend sin alterar el estado mostrado

#### Scenario: Transición gobernada por la revisión en dos instancias
- **WHEN** se envía al endpoint genérico de transiciones de un caso un código gobernado por la revisión en dos instancias
- **THEN** el sistema rechaza la operación sin ejecutar la transición, indicando que debe hacerse desde Revisión de Pagos

#### Scenario: Checklist documental vacío
- **WHEN** el `Proceso` del caso no tiene checklist documental generado todavía
- **THEN** la página muestra un estado vacío explícito en lugar de asumir una estructura de datos

### Requirement: Aviso y bloqueo de acciones gobernadas por Revisión de Pagos
Mientras el `Proceso` de un caso esté en `en_revision_finanzas` o `en_revision_zonal`, el sistema SHALL mostrar en la página de detalle del caso un aviso indicando que el pago está en revisión en dos instancias, con un enlace a Revisión de Pagos cuando el usuario tenga el permiso correspondiente, y SHALL rechazar la validación o el rechazo de documentos desde el endpoint genérico de esta pantalla.

#### Scenario: Aviso con enlace para un usuario con permiso de revisión
- **WHEN** un usuario con el permiso `pago_proveedores.revisar_finanzas` o `pago_proveedores.revisar_zonal` abre el detalle de un caso en `en_revision_finanzas` o `en_revision_zonal`
- **THEN** la página muestra un aviso con un enlace al egreso correspondiente en Revisión de Pagos

#### Scenario: Aviso sin enlace para un usuario sin permiso de revisión
- **WHEN** un usuario sin esos permisos abre el detalle de un caso en revisión
- **THEN** la página muestra el mismo aviso informativo sin un enlace accionable

#### Scenario: Validar o rechazar un documento queda bloqueado durante la revisión
- **WHEN** se intenta validar o rechazar un documento del proceso de un caso que está en `en_revision_finanzas` o `en_revision_zonal`, desde el endpoint genérico de documentos
- **THEN** el sistema rechaza la operación, indicando que debe hacerse desde Revisión de Pagos

### Requirement: Página de listado de egresos CGU
El sistema SHALL renderizar una página que muestre los egresos CGU paginados junto con los casos de pago de proveedores que cada uno cubre, y cada fila SHALL navegar al detalle de ese egreso.

#### Scenario: Listado con egresos
- **WHEN** un usuario autenticado visita la página de egresos CGU
- **THEN** la página muestra una fila por cada egreso, con su número, fecha, monto total y los `sgf_id` de los casos que cubre

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un egreso del listado
- **THEN** la aplicación navega a la página de detalle de ese egreso CGU

### Requirement: Página de detalle de egreso CGU
El sistema SHALL renderizar una página de detalle de un egreso CGU que muestre sus `egresos_cgu_items` (caso cubierto y monto).

#### Scenario: Ver el detalle de un egreso CGU
- **WHEN** un usuario autenticado visita la página de detalle de un egreso CGU
- **THEN** la página muestra el número de egreso, fecha, monto total, observaciones y la lista de casos cubiertos con su monto

### Requirement: Formulario de creación de egreso CGU
El sistema SHALL renderizar un formulario que permita elegir uno o más `casos_pago_proveedor` existentes, asignar un monto a cada uno, y enviar la creación del egreso CGU al endpoint ya existente.

#### Scenario: Crear un egreso cubriendo varios casos
- **WHEN** un usuario con permiso `pago_proveedores.registrar_egreso` selecciona dos o más casos, asigna un monto a cada uno y envía el formulario
- **THEN** el formulario envía `casos` como un arreglo de `{caso_pago_proveedor_id, monto}` al endpoint de creación

#### Scenario: Envío rechazado por el backend
- **WHEN** el backend rechaza la creación (validación o permiso)
- **THEN** el formulario muestra los errores de validación devueltos sin perder los valores ya ingresados
