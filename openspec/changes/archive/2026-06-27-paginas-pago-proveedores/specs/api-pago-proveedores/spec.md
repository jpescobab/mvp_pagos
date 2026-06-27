## MODIFIED Requirements

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

### Requirement: Listar y crear egresos CGU vía HTTP
El sistema SHALL exponer rutas autenticadas para listar `egresos_cgu`, mostrar un formulario de creación con los `casos_pago_proveedor` disponibles para cubrir, y crear un egreso nuevo que cubra uno o más de esos casos, exigiendo el permiso `pago_proveedores.registrar_egreso`.

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
