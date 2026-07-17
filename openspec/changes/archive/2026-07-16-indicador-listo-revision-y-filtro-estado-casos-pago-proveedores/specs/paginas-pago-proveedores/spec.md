## MODIFIED Requirements

### Requirement: Página de listado de casos de pago de proveedores
El sistema SHALL renderizar una página que muestre los casos de pago de proveedores paginados, con id (`sgf_id`), periodo, observación, observación de egreso, folio de egreso, RUT y nombre del proveedor, número, fecha SII, monto, estado SGF y estado actual del workflow. Los campos de referencia SGF que no estén disponibles para un caso SHALL mostrarse con un fallback explícito en vez de una celda vacía. La página SHALL soportar un filtro por estado del workflow, con los códigos de `EstadoWorkflow` del workflow `pago_proveedores` resueltos dinámicamente desde el backend (no hardcodeados en el frontend), enviado como parámetro de querystring y preservando la paginación existente. Cuando la página se visita sin ese parámetro, el sistema SHALL aplicar por defecto un filtro que excluye los casos en estado `lista_para_registro_cgu`, `registrada_en_cgu`, `lista_para_pago`, `pagada_bancoestado`, `asociada_a_egreso_cgu`, `cerrada`, `rechazada` o `anulada`, mostrando el resto. Un valor explícito de "todos los estados" en el parámetro SHALL desactivar ese filtro por defecto. Para cada caso cuyo `Proceso` esté en `en_revision_finanzas` o `en_revision_zonal`, la página SHALL mostrar además un indicador "Listo para revisar" cuando el caso cumpla, para la instancia de revisión correspondiente a su estado actual, el mismo criterio ya usado para habilitar la aprobación en Revisión de Pagos (documentos del checklist obligatorio aprobados y totales verificados); ese indicador es únicamente informativo y su presencia SHALL NOT disparar ni implicar ningún cambio de estado del `Proceso`.

#### Scenario: Listado con casos
- **WHEN** un usuario autenticado visita la página de casos de pago de proveedores
- **THEN** la página muestra una fila por cada caso recibido, con su `sgf_id`, periodo, observación, observación de egreso, folio de egreso, RUT y nombre del proveedor, número, fecha SII, monto y un badge del estado actual del `Proceso`

#### Scenario: Campo de referencia SGF no disponible
- **WHEN** un caso listado tiene `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero` o `fecha_sii` en `null`
- **THEN** la columna correspondiente muestra `"—"` en vez de una celda vacía

#### Scenario: Navegar al detalle desde el listado
- **WHEN** un usuario hace clic en un caso del listado
- **THEN** la aplicación navega a la página de detalle de ese caso

#### Scenario: Visita sin filtro de estado aplica el filtro por defecto
- **WHEN** un usuario autenticado visita la página de casos de pago de proveedores sin parámetro de estado en la URL
- **THEN** la página muestra únicamente casos en estado `importada_desde_sgf`, `recibida_finanzas`, `en_revision_finanzas`, `en_revision_zonal`, `observada` o `subsanada`

#### Scenario: Usuario elige ver todos los estados
- **WHEN** un usuario selecciona la opción de mostrar todos los estados en el filtro
- **THEN** la página incluye también los casos en estado `lista_para_registro_cgu`, `registrada_en_cgu`, `lista_para_pago`, `pagada_bancoestado`, `asociada_a_egreso_cgu`, `cerrada`, `rechazada` y `anulada`

#### Scenario: Usuario filtra por un estado específico
- **WHEN** un usuario selecciona un estado puntual del workflow en el filtro
- **THEN** la página muestra únicamente los casos cuyo `Proceso` está en ese estado, preservando la paginación

#### Scenario: Caso en revisión que cumple el criterio de aprobación muestra el indicador
- **WHEN** un caso está en `en_revision_finanzas` o `en_revision_zonal` y, para esa instancia, todos los documentos obligatorios del checklist están aprobados y los totales están verificados
- **THEN** la fila del caso muestra el indicador "Listo para revisar" junto al badge de estado

#### Scenario: Caso en revisión que no cumple el criterio no muestra el indicador
- **WHEN** un caso está en `en_revision_finanzas` o `en_revision_zonal` y falta al menos un documento obligatorio aprobado o los totales no están verificados para esa instancia
- **THEN** la fila del caso no muestra el indicador "Listo para revisar"

#### Scenario: El indicador no altera el estado del caso
- **WHEN** un caso alcanza el criterio de "Listo para revisar" en el listado
- **THEN** el estado del `Proceso` del caso permanece sin cambios hasta que un revisor con permiso ejecute la aprobación manual desde Revisión de Pagos
