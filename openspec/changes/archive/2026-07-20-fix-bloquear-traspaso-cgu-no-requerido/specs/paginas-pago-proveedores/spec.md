## MODIFIED Requirements

### Requirement: El detalle del caso muestra el traspaso importado de SGF y degrada el registro manual a corrección
En la página de detalle de un `caso_pago_proveedor`, el sistema SHALL mostrar el número de traspaso importado de SGF (`sgf_numero_traspaso`) como el Traspaso vigente en solo-lectura, identificado como proveniente de SGF, cuando exista y no haya un registro contable manual más reciente que lo corrija. El formulario de ingreso manual de Traspaso SHALL presentarse como una corrección puntual gateada por el permiso `pago_proveedores.registrar_cgu`, no como el ingreso primario. El criterio "al menos un registro contable CGU/Traspaso" del panel de preparación para Asignar Egreso SHALL considerarse cumplido cuando el caso tiene `sgf_numero_traspaso` no nulo, al menos un registro contable manual, o su `TipoProcesoPago` tiene `requiere_traspaso_cgu` en `false`. El formulario de ingreso manual de Traspaso SHALL además ocultarse, y el sistema SHALL rechazar por autorización cualquier intento de registrar uno, cuando el `TipoProcesoPago` del caso tiene `requiere_traspaso_cgu` en `false`, incluso para un usuario con el permiso `pago_proveedores.registrar_cgu`; los registros de Traspaso ya existentes para ese caso SHALL seguir mostrándose como referencia.

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

#### Scenario: Un tipo de proceso que no requiere traspaso oculta el formulario
- **WHEN** un usuario con el permiso `pago_proveedores.registrar_cgu` abre el detalle de un caso cuyo `TipoProcesoPago` tiene `requiere_traspaso_cgu` en `false`
- **THEN** la página no muestra el formulario de registro ni de corrección de Traspaso (CGU)
- **AND** muestra en su lugar un mensaje indicando que ese tipo de proceso no requiere Traspaso (CGU)

#### Scenario: El criterio de traspaso se cumple automáticamente cuando el tipo de proceso no lo requiere
- **WHEN** un caso cuyo `TipoProcesoPago` tiene `requiere_traspaso_cgu` en `false` no tiene `sgf_numero_traspaso` ni ningún registro contable manual
- **THEN** el criterio "Traspaso (CGU)" del panel de preparación para Asignar Egreso se muestra como cumplido

#### Scenario: Intentar registrar un traspaso para un tipo que no lo requiere es rechazado por el backend
- **WHEN** se envía una solicitud para registrar un Traspaso (CGU) sobre un caso cuyo `TipoProcesoPago` tiene `requiere_traspaso_cgu` en `false`, con el permiso `pago_proveedores.registrar_cgu`
- **THEN** el sistema rechaza la operación sin crear el registro

#### Scenario: Los registros de Traspaso existentes se conservan aunque el tipo ya no requiera traspaso
- **WHEN** un caso cuyo `TipoProcesoPago` tiene `requiere_traspaso_cgu` en `false` ya tiene registros contables CGU cargados
- **THEN** la página sigue mostrando esos registros existentes, sin el formulario de corrección

#### Scenario: Un caso sin tipo de proceso clasificado sigue exigiendo traspaso
- **WHEN** un usuario abre el detalle de un caso cuyo `Proceso` todavía no tiene `TipoProcesoPago` clasificado
- **THEN** la página muestra el formulario de corrección a un usuario con permiso, igual que hoy
- **AND** el criterio de traspaso del panel de preparación no se considera cumplido automáticamente
