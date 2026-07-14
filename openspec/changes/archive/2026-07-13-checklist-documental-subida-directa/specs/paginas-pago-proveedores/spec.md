## MODIFIED Requirements

### Requirement: Página de detalle de un caso con acciones de workflow
El sistema SHALL renderizar una página de detalle de un caso que muestre su estado actual, el checklist documental del proceso, el historial de transiciones, y permita ejecutar las transiciones disponibles delegando en el endpoint genérico ya existente, salvo las transiciones gobernadas por la revisión de pagos en dos instancias (`observar_finanzas`, `aprobar_finanzas`, `rechazar_finanzas`, `devolver_a_finanzas`, `aprobar_zonal`, `rechazar_zonal`), que el sistema SHALL rechazar desde este endpoint y que solo se ejecutan desde Revisión de Pagos. Cada ítem del checklist documental sin documento vinculado SHALL exponer un acceso directo para subir ese documento específico cuando el usuario tenga el permiso `documentos.gestionar`, sin requerir navegar a otra sección de la página ni seleccionar el tipo de documento manualmente. La página NO SHALL mostrar el volcado crudo (`payload_crudo`/`payload_normalizado`) del historial de snapshots SGF ni el estado SGF crudo (`sgf_status`) por separado del estado del workflow interno; la única acción relacionada con SGF que la página SHALL exponer es verificar el caso contra SGF cuando el usuario tenga el permiso correspondiente.

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
- **THEN** los ítems pendientes no muestran ningún control de subida

#### Scenario: La página no muestra el historial crudo de snapshots SGF
- **WHEN** un usuario autenticado visita el detalle de un caso con snapshots SGF registrados
- **THEN** la página no renderiza el listado de esos snapshots ni su `payload_crudo`/`payload_normalizado`

#### Scenario: La página no duplica el estado SGF crudo junto al estado del workflow
- **WHEN** un usuario autenticado visita el detalle de un caso
- **THEN** la página muestra únicamente el estado del `Proceso` (vía `EstadoBadge`), sin el texto de `sgf_status` por separado
