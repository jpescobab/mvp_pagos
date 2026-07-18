## ADDED Requirements

### Requirement: El detalle del caso muestra el traspaso importado de SGF y degrada el registro manual a corrección
En la página de detalle de un `caso_pago_proveedor`, el sistema SHALL mostrar el número de traspaso importado de SGF (`sgf_numero_traspaso`) como el Traspaso vigente en solo-lectura, identificado como proveniente de SGF, cuando exista y no haya un registro contable manual más reciente que lo corrija. El formulario de ingreso manual de Traspaso SHALL presentarse como una corrección puntual gateada por el permiso `pago_proveedores.registrar_cgu`, no como el ingreso primario. El criterio "al menos un registro contable CGU/Traspaso" del panel de preparación para Asignar Egreso SHALL considerarse cumplido cuando el caso tiene `sgf_numero_traspaso` no nulo o al menos un registro contable manual.

#### Scenario: El caso tiene traspaso importado de SGF
- **WHEN** un usuario abre el detalle de un caso con `sgf_numero_traspaso` no nulo y sin registro contable manual
- **THEN** la página muestra ese número como el Traspaso vigente, en solo-lectura, identificado como "desde SGF"
- **AND** no muestra el estado "Sin Traspaso registrado todavía"

#### Scenario: El panel de preparación cuenta el traspaso de SGF
- **WHEN** un caso tiene `sgf_numero_traspaso` no nulo y cumple los demás criterios de preparación
- **THEN** el criterio "al menos un Traspaso registrado" del panel se muestra como cumplido

#### Scenario: Corrección manual del traspaso desde el detalle
- **WHEN** un usuario con el permiso `pago_proveedores.registrar_cgu` registra una corrección de Traspaso sobre un caso que ya tiene `sgf_numero_traspaso`
- **THEN** la página muestra la corrección manual como el Traspaso vigente por encima del valor de SGF
- **AND** el valor importado de SGF permanece disponible como referencia

#### Scenario: Usuario sin permiso no ve la acción de corrección
- **WHEN** un usuario sin el permiso `pago_proveedores.registrar_cgu` abre el detalle de un caso con traspaso importado de SGF
- **THEN** la página muestra el Traspaso en solo-lectura, sin el formulario de corrección
