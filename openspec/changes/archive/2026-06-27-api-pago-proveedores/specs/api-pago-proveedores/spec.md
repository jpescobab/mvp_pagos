## ADDED Requirements

### Requirement: Listar y ver casos de pago de proveedores vía HTTP
El sistema SHALL exponer rutas autenticadas para listar `casos_pago_proveedor` y ver el detalle de uno, incluyendo el estado actual y el historial de transiciones de su `Proceso` asociado.

#### Scenario: Listar casos
- **WHEN** un usuario autenticado solicita la lista de casos de pago de proveedores
- **THEN** el sistema responde con una página Inertia que incluye los casos paginados

#### Scenario: Ver el detalle de un caso
- **WHEN** un usuario autenticado solicita el detalle de un caso de pago de proveedores
- **THEN** el sistema responde con una página Inertia que incluye el caso, su `Proceso`, estado actual e historial de transiciones

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
El sistema SHALL exponer rutas autenticadas para listar `egresos_cgu` y crear uno nuevo que cubra uno o más `casos_pago_proveedor`, exigiendo el permiso `pago_proveedores.registrar_egreso`.

#### Scenario: Crear un egreso CGU cubriendo varios casos
- **WHEN** un usuario con permiso `pago_proveedores.registrar_egreso` crea un egreso indicando uno o más casos
- **THEN** se crea el `egreso_cgu` y sus `egresos_cgu_items` correspondientes

#### Scenario: Rechazar crear un egreso sin permiso
- **WHEN** un usuario sin permiso `pago_proveedores.registrar_egreso` intenta crear un egreso CGU
- **THEN** el sistema rechaza la petición
- **AND** no se crea ningún `egreso_cgu`
