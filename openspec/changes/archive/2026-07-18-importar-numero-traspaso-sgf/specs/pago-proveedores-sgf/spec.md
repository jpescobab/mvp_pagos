## MODIFIED Requirements

### Requirement: Cada sgf_id es un caso de pago individual
El sistema SHALL tratar cada `sgf_id` como un `caso_pago_proveedor` independiente, con su propio `Proceso` de workflow. Los datos SGF (`sgf_status`, `sgf_current_group_raw`, `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero`, `fecha_sii`, `sgf_numero_traspaso`) SHALL conservarse solo como referencia externa, sin gobernar el estado interno del caso.

#### Scenario: Crear caso de pago desde un snapshot SGF
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) no tiene un `caso_pago_proveedor` previo
- **THEN** se crea un `caso_pago_proveedor`
- **AND** se crea un `Proceso` asociado en el estado inicial (`importada_desde_sgf`) del workflow "pago_proveedores"
- **AND** se conservan `sgf_status`, `sgf_current_group_raw`, `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero`, `fecha_sii` y `sgf_numero_traspaso` como referencia externa, cuando el payload normalizado del snapshot los incluye

#### Scenario: Reimportar un sgf_id existente no altera su workflow interno
- **WHEN** se registra un `snapshot_datos_externo` del sistema externo `SGF` cuyo `referencia_externa` (`sgf_id`) ya tiene un `caso_pago_proveedor`
- **THEN** se actualizan los campos de referencia SGF del caso (rut, monto, estado, grupo SGF, periodo, observación, observación de egreso, folio de egreso, número, fecha SII y número de traspaso SGF)
- **AND** el estado interno del `Proceso` asociado no cambia

#### Scenario: Un campo de referencia SGF no viene en el payload normalizado
- **WHEN** se registra o reimporta un `snapshot_datos_externo` de SGF cuyo payload normalizado no incluye `periodo`, `observacion`, `observacion_egreso`, `folio_egreso`, `numero`, `fecha_sii` o `sgf_numero_traspaso`
- **THEN** el `caso_pago_proveedor` conserva `null` en el campo faltante en vez de fallar la importación

## ADDED Requirements

### Requirement: El traspaso importado de SGF satisface el criterio de traspaso para egreso
El sistema SHALL considerar cumplido el criterio de "traspaso registrado" para avanzar un `caso_pago_proveedor` hacia la asignación de egreso cuando el caso tiene un `sgf_numero_traspaso` no nulo, sin requerir un `registro_contable_cgu` manual. Un `registro_contable_cgu` manual SHALL seguir satisfaciendo ese criterio de forma equivalente, y SHALL usarse para correcciones sin borrar ni sobrescribir el valor de referencia importado de SGF. Poblar `sgf_numero_traspaso` desde la importación SHALL NOT ejecutar ninguna transición de `TransicionWorkflowService` ni avanzar el estado del `Proceso`.

#### Scenario: Un caso con traspaso importado de SGF cumple el criterio sin registro manual
- **WHEN** se evalúa si un `caso_pago_proveedor` con `sgf_numero_traspaso` no nulo y sin ningún `registro_contable_cgu` está listo para asignar egreso, cumpliendo los demás criterios (tipo de proceso clasificado, checklist obligatorio completo y proveedor identificado)
- **THEN** el criterio de traspaso se considera cumplido

#### Scenario: Un caso sin traspaso de SGF ni registro manual no cumple el criterio
- **WHEN** se evalúa un `caso_pago_proveedor` con `sgf_numero_traspaso` nulo y sin ningún `registro_contable_cgu`
- **THEN** el criterio de traspaso no se considera cumplido

#### Scenario: La importación del traspaso no avanza el workflow
- **WHEN** una importación puebla `sgf_numero_traspaso` en un `caso_pago_proveedor` existente
- **THEN** el estado interno del `Proceso` del caso no cambia
- **AND** no se ejecuta ninguna transición de `TransicionWorkflowService`

#### Scenario: Corrección manual coexiste con el valor de SGF
- **WHEN** un usuario con el permiso `pago_proveedores.registrar_cgu` registra un `registro_contable_cgu` manual sobre un caso que ya tiene `sgf_numero_traspaso`
- **THEN** el `registro_contable_cgu` se conserva como corrección
- **AND** el `sgf_numero_traspaso` de referencia no se altera
