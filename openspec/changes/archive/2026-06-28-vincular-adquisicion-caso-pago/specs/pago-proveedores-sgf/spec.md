## ADDED Requirements

### Requirement: Vincular manualmente un caso de pago a un proceso de adquisición
El sistema SHALL permitir vincular un `caso_pago_proveedor` a un `proceso_adquisicion` mediante una acción manual y explícita, distinta de cualquier transición de workflow. El vínculo SHALL ser opcional (nullable) y SHALL permitir que varios `caso_pago_proveedor` apunten al mismo `proceso_adquisicion`, pero un `caso_pago_proveedor` SHALL apuntar a lo sumo a un `proceso_adquisicion` a la vez.

#### Scenario: Vincular un caso de pago a una adquisición
- **WHEN** un usuario con el permiso `pago_proveedores.vincular_adquisicion` selecciona un `proceso_adquisicion` desde la búsqueda asistida en el detalle de un `caso_pago_proveedor` sin vínculo previo
- **THEN** se registra `proceso_adquisicion_id` en el `caso_pago_proveedor`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.vincular_adquisicion`, el usuario, y el estado antes/después del vínculo
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Reemplazar o quitar un vínculo existente
- **WHEN** un usuario con el permiso `pago_proveedores.vincular_adquisicion` desvincula un `caso_pago_proveedor` que ya tenía `proceso_adquisicion_id` asignado
- **THEN** `proceso_adquisicion_id` queda en `null`
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.desvincular_adquisicion`

#### Scenario: Usuario sin permiso intenta vincular
- **WHEN** un usuario sin el permiso `pago_proveedores.vincular_adquisicion` intenta vincular o desvincular un `caso_pago_proveedor`
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

### Requirement: Búsqueda asistida de procesos de adquisición desde un caso de pago
El sistema SHALL exponer una búsqueda de `proceso_adquisicion` por código, objeto, proveedor o monto, limitada a un número acotado de resultados, sin intentar matching automático sobre el texto libre de SGF (`observaciones`, `payload_crudo`).

#### Scenario: Buscar procesos de adquisición candidatos
- **WHEN** un usuario autorizado escribe un término de búsqueda en la acción de vincular adquisición de un `caso_pago_proveedor`
- **THEN** el sistema devuelve los `proceso_adquisicion` cuyo código, objeto, proveedor o monto coincidan con el término, limitados a un máximo de resultados
- **AND** cada resultado muestra su código `ADQ-XXXX` para confirmación visual antes de vincular
