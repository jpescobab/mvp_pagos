## ADDED Requirements

### Requirement: Centro financiero por defecto configurable cuando no hay adquisición vinculada
El sistema SHALL resolver un `cfinanciero_id` por defecto, configurado vía parámetro de aplicación (`config('pago-proveedores.cfinanciero_default_codigo')`, con variable de entorno propia), para usar en `CasoPagoProveedor::cfinancieroId()` únicamente cuando el caso no tiene `proceso_adquisicion` vinculado. El vínculo real a `proceso_adquisicion` SHALL tener siempre prioridad sobre este default. El código configurado SHALL resolverse contra un `cfinanciero` existente y activo; si no resuelve, el sistema SHALL registrar un warning y comportarse como si no hubiera default configurado (sin lanzar una excepción visible al usuario).

#### Scenario: Caso sin adquisición vinculada usa el cfinanciero por defecto
- **WHEN** se consulta `cfinancieroId()` de un `caso_pago_proveedor` sin `proceso_adquisicion_id`
- **THEN** el sistema retorna el `cfinanciero_id` correspondiente al código configurado en `pago-proveedores.cfinanciero_default_codigo`

#### Scenario: Caso con adquisición vinculada ignora el default
- **WHEN** se consulta `cfinancieroId()` de un `caso_pago_proveedor` con `proceso_adquisicion_id` asignado y su `proceso_adquisicion->ccosto->cfinanciero_id` resuelto
- **THEN** el sistema retorna ese `cfinanciero_id` real, no el default configurado

#### Scenario: Código configurado no corresponde a un cfinanciero activo
- **WHEN** el código configurado en `pago-proveedores.cfinanciero_default_codigo` no coincide con ningún `cfinanciero` activo
- **THEN** el sistema registra un warning en el log
- **AND** `cfinancieroId()` retorna `null` para casos sin adquisición vinculada, igual que si no existiera default configurado

### Requirement: La aprobación desde Finanzas ya no se bloquea por falta de adquisición cuando hay default configurado
`RevisionEgresoService::aprobarPago()` SHALL considerar la jurisdicción determinable cuando `CasoPagoProveedor::cfinancieroId()` retorna un valor no nulo, incluyendo el caso en que ese valor provenga del cfinanciero por defecto configurado. Al aprobar exitosamente desde la instancia Finanzas, el sistema SHALL persistir ese `cfinanciero_id` (real o default) en el `EgresoCgu` asociado si aún no tiene uno asignado, para que la revisión de la instancia Zonal pueda filtrar por jurisdicción.

#### Scenario: Aprobar desde Finanzas sin adquisición vinculada pero con default configurado
- **WHEN** un usuario con `pago_proveedores.revisar_finanzas` aprueba un pago cuyo `caso_pago_proveedor` no tiene `proceso_adquisicion` vinculado, y hay un cfinanciero por defecto configurado y resoluble
- **THEN** la aprobación no se bloquea por el guardrail de jurisdicción
- **AND** el `EgresoCgu` asociado queda con `cfinanciero_id` igual al valor por defecto, si no tenía uno asignado previamente

#### Scenario: Aprobar desde Finanzas sin adquisición vinculada y sin default configurado
- **WHEN** un usuario con `pago_proveedores.revisar_finanzas` intenta aprobar un pago cuyo `caso_pago_proveedor` no tiene `proceso_adquisicion` vinculado, y no hay cfinanciero por defecto configurado o resoluble
- **THEN** el sistema bloquea la aprobación con el mensaje existente indicando vincular el caso a un Proceso de Adquisición
