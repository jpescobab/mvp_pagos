## ADDED Requirements

### Requirement: Cada sgf_id es un caso de pago individual
El sistema SHALL tratar cada `sgf_id` como un `caso_pago_proveedor` independiente, con su propio `Proceso` de workflow. Los datos SGF (`sgf_status`, `sgf_current_group_raw`) SHALL conservarse solo como referencia externa, sin gobernar el estado interno del caso.

#### Scenario: Crear caso de pago desde un snapshot SGF
- **WHEN** se importa un `SnapshotSgf` cuyo `sgf_id` no tiene un `caso_pago_proveedor` previo
- **THEN** se crea un `caso_pago_proveedor`
- **AND** se crea un `Proceso` asociado en el estado inicial (`importada_desde_sgf`) del workflow "pago_proveedores"
- **AND** se conservan `sgf_status` y `sgf_current_group_raw` como referencia externa

#### Scenario: Reimportar un sgf_id existente no altera su workflow interno
- **WHEN** se importa un `SnapshotSgf` cuyo `sgf_id` ya tiene un `caso_pago_proveedor`
- **THEN** se actualizan los campos de referencia SGF del caso (rut, monto, estado y grupo SGF)
- **AND** el estado interno del `Proceso` asociado no cambia

### Requirement: No modelar lotes ni envíos iniciales
El sistema SHALL NOT crear `payment_submissions`, `payment_submission_items` ni un `sgf_submission_id` al importar casos desde SGF.

#### Scenario: Importar sin generar lotes
- **WHEN** se importan una o más filas SGF como casos de pago
- **THEN** no se crea ningún registro de lote o envío agrupado
- **AND** cada caso se gobierna de forma individual por su propio `Proceso`

### Requirement: Registrar CGU, BancoEstado y egreso CGU como evidencia
El sistema SHALL registrar referencias y respaldos de registro contable CGU, pago BancoEstado y egreso CGU como evidencia de gestión, sin reemplazar la lógica de esos sistemas oficiales.

#### Scenario: Asociar un egreso CGU a uno o más casos
- **WHEN** se registra un egreso CGU que cubre uno o más casos ya pagados
- **THEN** se crea un `egreso_cgu`
- **AND** se asocian los casos correspondientes mediante `egresos_cgu_items`
- **AND** se puede vincular respaldo documental al egreso mediante `vinculos_documento`
