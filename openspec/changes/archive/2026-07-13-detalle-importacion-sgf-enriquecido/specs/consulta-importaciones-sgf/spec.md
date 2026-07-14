## MODIFIED Requirements

### Requirement: Ver el detalle de una corrida de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, el detalle de un `trabajo_integracion` del sistema externo `SGF` junto con todos los `snapshots_datos_externos` que produjo. Por cada snapshot, el sistema SHALL incluir los datos normalizados disponibles en `payload_normalizado` (proveedor, monto, estado SGF, folio de egreso, número, período, fecha SII, observaciones), usando un valor vacío explícito para cualquier campo no capturado en ese snapshot. Cuando exista un `caso_pago_proveedor` cuyo `sgf_id` coincida con la `referencia_externa` del snapshot, el sistema SHALL incluir su id y el estado actual de su workflow interno. El sistema SHALL además incluir un resumen agregado de la corrida con el monto total de los snapshots producidos y la cantidad de proveedores identificados (con `Proveedor` coincidente por RUT) versus no identificados.

#### Scenario: Ver los snapshots producidos por una corrida con sus datos normalizados
- **WHEN** un usuario autenticado abre el detalle de un `trabajo_integracion` de SGF
- **THEN** la respuesta incluye todos los `snapshots_datos_externos` asociados a ese `trabajo_integracion`, cada uno con su `referencia_externa` (`sgf_id`), hash, fecha de captura (`capturado_en`) y los campos normalizados disponibles (proveedor, monto, estado SGF, folio de egreso, número, período, fecha SII, observaciones)

#### Scenario: Un snapshot con payload normalizado incompleto no rompe el detalle
- **WHEN** un `snapshot_datos_externo` tiene `payload_normalizado` sin alguna de las claves normalizadas (por ejemplo, sin `monto` o sin `folio_egreso`)
- **THEN** la respuesta incluye ese snapshot igual, con los campos faltantes en `null`

#### Scenario: Un snapshot ya importado enlaza a su caso de pago
- **WHEN** existe un `caso_pago_proveedor` cuyo `sgf_id` coincide con la `referencia_externa` de un snapshot del detalle
- **THEN** la respuesta incluye el id de ese `caso_pago_proveedor` y el código del estado actual de su workflow interno para ese snapshot

#### Scenario: Un snapshot sin caso de pago asociado no incluye enlace
- **WHEN** ningún `caso_pago_proveedor` tiene un `sgf_id` que coincida con la `referencia_externa` de un snapshot del detalle
- **THEN** la respuesta incluye ese snapshot con el id de caso y el estado en `null`, sin error

#### Scenario: El detalle incluye el resumen financiero de la corrida
- **WHEN** un usuario autenticado abre el detalle de un `trabajo_integracion` de SGF con snapshots producidos
- **THEN** la respuesta incluye el monto total sumado de esos snapshots y la cantidad de proveedores identificados versus no identificados
