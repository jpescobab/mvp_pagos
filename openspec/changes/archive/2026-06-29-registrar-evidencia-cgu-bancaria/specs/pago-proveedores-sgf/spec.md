## MODIFIED Requirements

### Requirement: Registrar CGU, BancoEstado y egreso CGU como evidencia
El sistema SHALL registrar referencias y respaldos de registro contable CGU, pago BancoEstado y egreso CGU como evidencia de gestión, sin reemplazar la lógica de esos sistemas oficiales. El registro contable CGU y el pago BancoEstado SHALL ser registros manuales independientes de cualquier transición de workflow, autorizados respectivamente por los permisos `pago_proveedores.registrar_cgu` y `pago_proveedores.pagar`.

#### Scenario: Asociar un egreso CGU a uno o más casos
- **WHEN** se registra un egreso CGU que cubre uno o más casos ya pagados
- **THEN** se crea un `egreso_cgu`
- **AND** se asocian los casos correspondientes mediante `egresos_cgu_items`
- **AND** se puede vincular respaldo documental al egreso mediante `vinculos_documento`

#### Scenario: Registrar evidencia de registro contable CGU
- **WHEN** un usuario con el permiso `pago_proveedores.registrar_cgu` registra un número de registro, fecha y monto para un `caso_pago_proveedor`
- **THEN** se crea un `registro_contable_cgu` asociado al caso, con el usuario autenticado como `registrado_por`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.registrar_contable_cgu`
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Registrar evidencia de pago bancario
- **WHEN** un usuario con el permiso `pago_proveedores.pagar` registra un número de operación, fecha de pago y monto para un `caso_pago_proveedor`
- **THEN** se crea un `registro_pago_bancario` asociado al caso, con el usuario autenticado como `registrado_por`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.registrar_pago_bancario`
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Usuario sin permiso intenta registrar evidencia
- **WHEN** un usuario sin el permiso `pago_proveedores.registrar_cgu` intenta registrar un `registro_contable_cgu`, o un usuario sin el permiso `pago_proveedores.pagar` intenta registrar un `registro_pago_bancario`
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

#### Scenario: El detalle de un caso de pago muestra el historial completo de evidencia
- **WHEN** un usuario abre el detalle de un `caso_pago_proveedor`
- **THEN** la respuesta incluye todos los `registro_contable_cgu` y `registro_pago_bancario` asociados al caso, no solo el más reciente
