## MODIFIED Requirements

### Requirement: Página de detalle de un caso con acciones de workflow
El sistema SHALL renderizar una página de detalle de un caso que muestre su estado actual, el checklist documental del proceso, el historial de transiciones, y permita ejecutar las transiciones disponibles delegando en el endpoint genérico ya existente, salvo las transiciones gobernadas por la revisión de pagos en dos instancias (`observar_finanzas`, `aprobar_finanzas`, `rechazar_finanzas`, `devolver_a_finanzas`, `aprobar_zonal`, `rechazar_zonal`), que el sistema SHALL rechazar desde este endpoint y que solo se ejecutan desde Revisión de Pagos. Cada ítem del checklist documental sin documento vinculado SHALL exponer un acceso directo para subir ese documento específico cuando el usuario tenga el permiso `documentos.gestionar`, sin requerir navegar a otra sección de la página ni seleccionar el tipo de documento manualmente; SHALL además exponer, cuando existan, los documentos del caso ya vinculados que no coinciden con ningún ítem del checklist actual ("huérfanos"), permitiendo vincular uno de ellos a ese ítem sin volver a subirlo. Cada ítem del checklist con un documento vinculado SHALL exponer una vista previa embebida de ese documento y, cuando el usuario tenga el permiso `documentos.gestionar`, un control para desvincularlo, ambos sin salir de la página ni descargar el archivo. La página NO SHALL mostrar el volcado crudo (`payload_crudo`/`payload_normalizado`) del historial de snapshots SGF ni el estado SGF crudo (`sgf_status`) por separado del estado del workflow interno; la única acción relacionada con SGF que la página SHALL exponer es verificar el caso contra SGF cuando el usuario tenga el permiso correspondiente. La página SHALL además mostrar, al inicio, un panel de preparación para Asignar Egreso con los 4 criterios de disposición del caso (tipo de proceso de pago clasificado, al menos un registro contable CGU/Traspaso, todos los ítems obligatorios del checklist con documento vinculado, `Proveedor` identificado), derivados de los datos ya presentes en la respuesta de la página sin requerir una petición adicional. Cuando el caso no tiene ningún Egreso CGU asociado todavía y los 4 criterios de ese panel están completos, la página SHALL mostrar un acceso directo hacia el formulario de creación de Egreso CGU con este caso preseleccionado, sin ejecutar ninguna transición de workflow por sí misma.

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

#### Scenario: Ver un documento del checklist embebido sin descargarlo
- **WHEN** un usuario visualiza un ítem del checklist que ya tiene un documento vinculado
- **THEN** la página permite abrir una vista previa embebida de ese documento sin descargarlo ni navegar fuera de la página

#### Scenario: Desvincular un documento directamente desde un ítem del checklist
- **WHEN** un usuario con el permiso `documentos.gestionar` desvincula, desde un ítem del checklist, el documento asociado a ese ítem
- **THEN** la página envía la desvinculación al endpoint existente y el ítem vuelve a mostrarse como pendiente tras la respuesta

#### Scenario: Usuario sin permiso de gestión de documentos no ve el control de desvinculación
- **WHEN** un usuario sin el permiso `documentos.gestionar` visualiza un ítem del checklist con un documento vinculado
- **THEN** el ítem muestra la vista previa pero no el control de desvinculación

#### Scenario: Acceso directo visible cuando el caso está listo y sin egreso
- **WHEN** un caso no tiene ningún Egreso CGU asociado y los 4 criterios del panel de preparación están completos
- **THEN** la página muestra un enlace hacia el formulario de creación de Egreso CGU con este caso preseleccionado

#### Scenario: Acceso directo ausente cuando el caso ya tiene un egreso asociado
- **WHEN** un caso ya tiene al menos un Egreso CGU asociado
- **THEN** la página no muestra el acceso directo de creación de egreso, independientemente del estado del panel de preparación

#### Scenario: Acceso directo ausente cuando el caso no está listo
- **WHEN** un caso no tiene Egreso CGU asociado pero le falta al menos uno de los 4 criterios de preparación
- **THEN** la página no muestra el acceso directo de creación de egreso

### Requirement: Formulario de creación de egreso CGU
El sistema SHALL renderizar un formulario que permita elegir uno o más `casos_pago_proveedor` existentes, asignar un monto a cada uno, y enviar la creación del egreso CGU al endpoint ya existente. Cuando la página se visita con un parámetro que identifica una corrida de importación SGF, el sistema SHALL limitar la lista de casos disponibles a los de esa corrida que aún no tengan un Egreso CGU asignado, y SHALL preseleccionar únicamente los que estén marcados como listos para Asignar Egreso, dejando visibles pero sin marcar los que no lo estén. Cuando la página se visita con un parámetro que identifica un caso puntual (llegado desde el acceso directo del detalle de ese caso), el sistema SHALL preseleccionar únicamente ese caso dentro de la lista completa de casos disponibles, sin restringir la lista a ese caso ni excluir a los demás.

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

#### Scenario: Acceder al formulario desde el detalle de un caso puntual preselecciona solo ese caso
- **WHEN** un usuario visita el formulario de creación de egreso con el identificador de un caso puntual
- **THEN** la lista incluye todos los `casos_pago_proveedor` pendientes de asignar a un egreso
- **AND** únicamente ese caso queda preseleccionado, sin excluir a los demás de la lista

#### Scenario: El caso puntual ya no está disponible al llegar al formulario
- **WHEN** un usuario visita el formulario de creación de egreso con el identificador de un caso puntual que mientras tanto ya obtuvo un Egreso CGU
- **THEN** la lista no incluye ese caso y ninguna fila queda preseleccionada por ese parámetro
