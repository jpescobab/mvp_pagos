## MODIFIED Requirements

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
