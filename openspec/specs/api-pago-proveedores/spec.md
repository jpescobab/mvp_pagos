## Purpose

Expone la capa de presentación HTTP/Inertia para el dominio de pago de proveedores: listar y ver casos, ejecutar transiciones de workflow a través de `TransicionWorkflowService::execute()`, y listar/crear egresos CGU. No define reglas de negocio propias — estas viven en `pago-proveedores-sgf`; esta capability solo cubre rutas, controladores y autorización HTTP sobre esas reglas.

## Requirements

### Requirement: Listar y ver casos de pago de proveedores vía HTTP
El sistema SHALL exponer rutas autenticadas para listar `casos_pago_proveedor` y ver el detalle de uno, incluyendo el estado actual, el historial de transiciones y el checklist documental de su `Proceso` asociado.

#### Scenario: Listar casos
- **WHEN** un usuario autenticado solicita la lista de casos de pago de proveedores
- **THEN** el sistema responde con una página Inertia que incluye los casos paginados

#### Scenario: Ver el detalle de un caso
- **WHEN** un usuario autenticado solicita el detalle de un caso de pago de proveedores
- **THEN** el sistema responde con una página Inertia que incluye el caso, su `Proceso`, estado actual e historial de transiciones

#### Scenario: Ver el checklist documental del proceso
- **WHEN** un usuario autenticado solicita el detalle de un caso cuyo `Proceso` tiene un `ChecklistDocumentalProceso` generado
- **THEN** la respuesta incluye los items del checklist (tipo de documento, tipo de requisito, estado de cumplimiento)

#### Scenario: Caso sin checklist generado
- **WHEN** un usuario autenticado solicita el detalle de un caso cuyo `Proceso` no tiene `ChecklistDocumentalProceso` generado todavía
- **THEN** la respuesta refleja la ausencia de checklist sin error

### Requirement: Ejecutar transiciones de workflow vía un endpoint genérico
El sistema SHALL exponer un único endpoint HTTP que reciba el código de una transición y lo delegue íntegramente a `TransicionWorkflowService::execute()`, sin duplicar su lógica de autorización, comentario requerido ni documentos obligatorios.

#### Scenario: Ejecutar una transición válida
- **WHEN** un usuario con el permiso requerido envía un código de transición válido para el estado actual de un caso
- **THEN** el `Proceso` del caso transiciona al estado destino
- **AND** la respuesta refleja el nuevo estado

#### Scenario: Rechazar una transición sin permiso o inválida
- **WHEN** un usuario sin el permiso requerido, o con un código de transición no válido para el estado actual, intenta ejecutar una transición
- **THEN** el sistema rechaza la petición sin modificar el estado del `Proceso`
- **AND** la excepción de `TransicionWorkflowService` se traduce a una respuesta HTTP de error apropiada

### Requirement: Listar y crear egresos CGU vía HTTP
El sistema SHALL exponer rutas autenticadas para listar `egresos_cgu`, ver el detalle de uno (incluyendo sus `egresos_cgu_items` y sus documentos vinculados), mostrar un formulario de creación con los `casos_pago_proveedor` disponibles para cubrir, y crear un egreso nuevo que cubra uno o más de esos casos, exigiendo el permiso `pago_proveedores.registrar_egreso` para crear.

#### Scenario: Crear un egreso CGU cubriendo varios casos
- **WHEN** un usuario con permiso `pago_proveedores.registrar_egreso` crea un egreso indicando uno o más casos
- **THEN** se crea el `egreso_cgu` y sus `egresos_cgu_items` correspondientes

#### Scenario: Rechazar crear un egreso sin permiso
- **WHEN** un usuario sin permiso `pago_proveedores.registrar_egreso` intenta crear un egreso CGU
- **THEN** el sistema rechaza la petición
- **AND** no se crea ningún `egreso_cgu`

#### Scenario: Formulario de creación incluye los casos disponibles
- **WHEN** un usuario con permiso `pago_proveedores.registrar_egreso` solicita el formulario de creación de un egreso CGU
- **THEN** la respuesta incluye la lista de `casos_pago_proveedor` existentes para que el usuario elija cuáles cubre el egreso

#### Scenario: Ver el detalle de un egreso CGU
- **WHEN** un usuario autenticado solicita el detalle de un `egreso_cgu` existente
- **THEN** la respuesta incluye sus `egresos_cgu_items` (con el `sgf_id` y monto de cada caso cubierto) y sus documentos vinculados activos
