## ADDED Requirements

### Requirement: Clasificar el tipo de proceso o de pago de un caso
El sistema SHALL permitir clasificar cada `caso_pago_proveedor` con un tipo de proceso de pago (`COMPRA`, `CONTRATO`, `CONVENIO`, `REEMBOLSO`, `ANTICIPO`, `OTRO`, catálogo `tipos_proceso_pago`), a un usuario con el permiso `pago_proveedores.gestionar_caso`, mediante una acción explícita distinta de cualquier transición de workflow. La clasificación SHALL persistirse en `procesos.tipo_proceso_pago_id`.

#### Scenario: Clasificar el tipo de proceso de un caso
- **WHEN** un usuario con `pago_proveedores.gestionar_caso` selecciona un tipo de proceso de pago activo en el detalle de un `caso_pago_proveedor`
- **THEN** se registra `tipo_proceso_pago_id` en el `Proceso` del caso
- **AND** se registra un evento de auditoría con la acción, el usuario, y el valor antes/después
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Usuario sin permiso intenta clasificar
- **WHEN** un usuario sin `pago_proveedores.gestionar_caso` intenta clasificar el tipo de proceso de un caso
- **THEN** el sistema bloquea la operación

#### Scenario: Cambiar el tipo de proceso ya clasificado
- **WHEN** un usuario con `pago_proveedores.gestionar_caso` selecciona un tipo de proceso distinto al ya registrado en un caso
- **THEN** `procesos.tipo_proceso_pago_id` se actualiza al nuevo valor
- **AND** el checklist documental refleja los requisitos del nuevo tipo en la siguiente resolución
