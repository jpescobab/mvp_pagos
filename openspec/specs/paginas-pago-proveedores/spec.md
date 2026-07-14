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
El sistema SHALL renderizar una página de detalle de un caso que muestre su estado actual, el checklist documental del proceso, el historial de transiciones, y permita ejecutar las transiciones disponibles delegando en el endpoint genérico ya existente, salvo las transiciones gobernadas por la revisión de pagos en dos instancias (`observar_finanzas`, `aprobar_finanzas`, `rechazar_finanzas`, `devolver_a_finanzas`, `aprobar_zonal`, `rechazar_zonal`), que el sistema SHALL rechazar desde este endpoint y que solo se ejecutan desde Revisión de Pagos. Cada ítem del checklist documental sin documento vinculado SHALL exponer un acceso directo para subir ese documento específico cuando el usuario tenga el permiso `documentos.gestionar`, sin requerir navegar a otra sección de la página ni seleccionar el tipo de documento manualmente; SHALL además exponer, cuando existan, los documentos del caso ya vinculados que no coinciden con ningún ítem del checklist actual ("huérfanos"), permitiendo vincular uno de ellos a ese ítem sin volver a subirlo. La página NO SHALL mostrar el volcado crudo (`payload_crudo`/`payload_normalizado`) del historial de snapshots SGF ni el estado SGF crudo (`sgf_status`) por separado del estado del workflow interno; la única acción relacionada con SGF que la página SHALL exponer es verificar el caso contra SGF cuando el usuario tenga el permiso correspondiente. La página SHALL además mostrar, al inicio, un panel de preparación para Asignar Egreso con los 4 criterios de disposición del caso (tipo de proceso de pago clasificado, al menos un registro contable CGU/Traspaso, todos los ítems obligatorios del checklist con documento vinculado, `Proveedor` identificado), derivados de los datos ya presentes en la respuesta de la página sin requerir una petición adicional.

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

#### Scenario: Subir un documento directamente desde un ítem pendiente del checklist
- **WHEN** un usuario con el permiso `documentos.gestionar` elige un archivo desde el acceso directo de un ítem del checklist cuyo `estado_cumplimiento` es `pendiente`
- **THEN** la página sube ese archivo vinculado al `tipo_documento_id` de ese ítem específico, sin requerir seleccionar el tipo desde el formulario general de la sección "Documentos"

#### Scenario: Usuario sin permiso de gestión de documentos no ve el acceso directo
- **WHEN** un usuario sin el permiso `documentos.gestionar` visualiza el checklist documental
- **THEN** los ítems pendientes no muestran ningún control de subida ni de vinculación

#### Scenario: La página no muestra el historial crudo de snapshots SGF
- **WHEN** un usuario autenticado visita el detalle de un caso con snapshots SGF registrados
- **THEN** la página no renderiza el listado de esos snapshots ni su `payload_crudo`/`payload_normalizado`

#### Scenario: La página no duplica el estado SGF crudo junto al estado del workflow
- **WHEN** un usuario autenticado visita el detalle de un caso
- **THEN** la página muestra únicamente el estado del `Proceso` (vía `EstadoBadge`), sin el texto de `sgf_status` por separado

#### Scenario: Vincular un documento huérfano a un ítem pendiente del checklist
- **WHEN** un usuario con el permiso `documentos.gestionar` selecciona, desde un ítem pendiente del checklist, un documento del caso que no coincide con ningún ítem actual
- **THEN** la página reclasifica ese documento al `tipo_documento_id` del ítem elegido, sin requerir subir un archivo nuevo

#### Scenario: Un caso sin documentos huérfanos no muestra el control de vinculación
- **WHEN** un usuario visualiza un ítem pendiente del checklist y el caso no tiene ningún documento vinculado que no coincida con el checklist actual
- **THEN** el ítem solo muestra el acceso directo de subida, sin el selector de documentos huérfanos

#### Scenario: El panel de preparación refleja un caso completamente listo
- **WHEN** un caso tiene tipo de proceso clasificado, al menos un Traspaso registrado, todos los ítems obligatorios del checklist con documento vinculado, y `Proveedor` identificado
- **THEN** el panel de preparación muestra los 4 criterios como cumplidos

#### Scenario: El panel de preparación refleja un caso incompleto
- **WHEN** a un caso le falta al menos uno de los 4 criterios de preparación
- **THEN** el panel de preparación muestra únicamente ese criterio como pendiente, sin afectar la disponibilidad de las demás acciones de la página

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
El sistema SHALL renderizar un formulario que permita elegir uno o más `casos_pago_proveedor` existentes, asignar un monto a cada uno, y enviar la creación del egreso CGU al endpoint ya existente. Cuando la página se visita con un parámetro que identifica una corrida de importación SGF, el sistema SHALL limitar la lista de casos disponibles a los de esa corrida que aún no tengan un Egreso CGU asignado, y SHALL preseleccionar únicamente los que estén marcados como listos para Asignar Egreso, dejando visibles pero sin marcar los que no lo estén.

#### Scenario: Crear un egreso cubriendo varios casos
- **WHEN** un usuario con permiso `pago_proveedores.registrar_egreso` selecciona dos o más casos, asigna un monto a cada uno y envía el formulario
- **THEN** el formulario envía `casos` como un arreglo de `{caso_pago_proveedor_id, monto}` al endpoint de creación

#### Scenario: Envío rechazado por el backend
- **WHEN** el backend rechaza la creación (validación o permiso)
- **THEN** el formulario muestra los errores de validación devueltos sin perder los valores ya ingresados

#### Scenario: Acceder al formulario desde una importación SGF preselecciona los casos listos
- **WHEN** un usuario visita el formulario de creación de egreso con el identificador de una corrida de importación SGF
- **THEN** la lista de casos se limita a los de esa corrida sin Egreso CGU asignado
- **AND** los casos marcados como listos para Asignar Egreso quedan preseleccionados, y los demás visibles sin seleccionar

#### Scenario: Acceder al formulario sin parámetro de importación mantiene el comportamiento actual
- **WHEN** un usuario visita el formulario de creación de egreso sin ningún parámetro de importación
- **THEN** la lista incluye todos los `casos_pago_proveedor` pendientes de asignar a un egreso, sin ninguna preselección
