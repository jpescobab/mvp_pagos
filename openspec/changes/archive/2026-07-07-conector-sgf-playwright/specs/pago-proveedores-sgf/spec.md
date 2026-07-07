## MODIFIED Requirements

### Requirement: Cada sgf_id es un caso de pago individual
El sistema SHALL tratar cada `sgf_id` como un `caso_pago_proveedor` independiente, con su propio `Proceso` de workflow. Los datos SGF (`sgf_status`, `sgf_current_group_raw`) SHALL conservarse solo como referencia externa, sin gobernar el estado interno del caso.

#### Scenario: Crear caso de pago desde un snapshot SGF
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) no tiene un `caso_pago_proveedor` previo
- **THEN** se crea un `caso_pago_proveedor`
- **AND** se crea un `Proceso` asociado en el estado inicial (`importada_desde_sgf`) del workflow "pago_proveedores"
- **AND** se conservan `sgf_status` y `sgf_current_group_raw` como referencia externa

#### Scenario: Reimportar un sgf_id existente no altera su workflow interno
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) ya tiene un `caso_pago_proveedor`
- **THEN** se actualizan los campos de referencia SGF del caso (rut, monto, estado y grupo SGF)
- **AND** el estado interno del `Proceso` asociado no cambia
