# Spec: registrar-factura-caso-pago-proveedor

## Purpose

Registrar y consultar la factura (folio, monto, fecha de emisión) como dato estructurado asociado a un `caso_pago_proveedor`, distinto e independiente del documento `FACTURA` (archivo/evidencia) del expediente documental.

## Requirements

### Requirement: Registrar factura como dato estructurado del caso de pago
El sistema SHALL permitir registrar una factura (folio, monto, fecha de emisión) como dato estructurado asociado a un `caso_pago_proveedor`, distinto e independiente del documento `FACTURA` (archivo/evidencia) del expediente documental. El registro SHALL ser una operación manual independiente de cualquier transición de workflow, autorizada por el permiso `pago_proveedores.registrar_factura`.

#### Scenario: Registrar una factura para un caso de pago
- **WHEN** un usuario con el permiso `pago_proveedores.registrar_factura` registra un folio, monto y fecha de emisión para un `caso_pago_proveedor`
- **THEN** se crea una `factura` asociada al caso, con el `proveedor_id` del caso
- **AND** se registra un evento de auditoría con la acción `caso_pago_proveedor.registrar_factura`
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Usuario sin permiso intenta registrar una factura
- **WHEN** un usuario sin el permiso `pago_proveedores.registrar_factura` intenta registrar una `factura` para un `caso_pago_proveedor`
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

#### Scenario: El detalle de un caso de pago muestra las facturas registradas
- **WHEN** un usuario abre el detalle de un `caso_pago_proveedor`
- **THEN** la respuesta incluye todas las `factura` asociadas al caso (folio, monto, fecha de emisión), no solo la más reciente
