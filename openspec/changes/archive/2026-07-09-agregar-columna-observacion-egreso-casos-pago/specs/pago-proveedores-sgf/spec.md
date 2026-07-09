## MODIFIED Requirements

### Requirement: Cada sgf_id es un caso de pago individual
El sistema SHALL tratar cada `sgf_id` como un `caso_pago_proveedor` independiente, con su propio `Proceso` de workflow. Los datos SGF (`sgf_status`, `sgf_current_group_raw`, `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero`, `fecha_sii`) SHALL conservarse solo como referencia externa, sin gobernar el estado interno del caso.

#### Scenario: Crear caso de pago desde un snapshot SGF
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) no tiene un `caso_pago_proveedor` previo
- **THEN** se crea un `caso_pago_proveedor`
- **AND** se crea un `Proceso` asociado en el estado inicial (`importada_desde_sgf`) del workflow "pago_proveedores"
- **AND** se conservan `sgf_status`, `sgf_current_group_raw`, `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero` y `fecha_sii` como referencia externa, cuando el payload normalizado del snapshot los incluye

#### Scenario: Reimportar un sgf_id existente no altera su workflow interno
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) ya tiene un `caso_pago_proveedor`
- **THEN** se actualizan los campos de referencia SGF del caso (rut, monto, estado, grupo SGF, periodo, observación, observación de egreso, folio de egreso, número y fecha SII)
- **AND** el estado interno del `Proceso` asociado no cambia

#### Scenario: Un campo de referencia SGF no viene en el payload normalizado
- **WHEN** se registra o reimporta un `snapshot_datos_externo` de SGF cuyo payload normalizado no incluye `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero` o `fecha_sii`
- **THEN** el `caso_pago_proveedor` conserva `null` en el campo faltante en vez de fallar la importación
