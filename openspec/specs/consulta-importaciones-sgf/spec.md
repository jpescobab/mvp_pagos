# Spec: consulta-importaciones-sgf

## Purpose

Exponer, de solo lectura, el historial de corridas de importación SGF (`trabajos_integracion` del sistema externo `SGF`) y los snapshots que cada una produjo, para que sea consultable en vez de solo verificable directamente en la base de datos.
## Requirements
### Requirement: Listar las corridas de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, un listado paginado de los `trabajos_integracion` del sistema externo `SGF`, ordenado del más reciente al más antiguo, con su tipo (verificación puntual o importación masiva), mecanismo, quién lo inició, fecha de inicio y fin, total de elementos y estado. El sistema SHALL permitir filtrar ese listado mediante un término de búsqueda opcional que coincida con el tipo de corrida o con el nombre del usuario que la inició, conservando el resto de corridas fuera de ese filtro cuando no se proporciona término.

#### Scenario: Listar corridas de importación
- **WHEN** un usuario autenticado visita el listado de importaciones SGF sin término de búsqueda
- **THEN** la respuesta incluye los `trabajos_integracion` del sistema externo `SGF` paginados, ordenados del más reciente al más antiguo, cada uno con su tipo, usuario que lo inició, fechas de inicio/fin, total de elementos y estado

#### Scenario: Filtrar corridas por término de búsqueda
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que coincide con el tipo de corrida o con el nombre del usuario que la inició
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` de SGF cuyo tipo o usuario que los inició coincide con el término, paginados igual que el listado sin filtrar

#### Scenario: Búsqueda sin resultados
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que no coincide con ningún `trabajo_integracion` de SGF
- **THEN** la respuesta incluye un listado vacío, sin error

### Requirement: Ver el detalle de una corrida de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, el detalle de un `trabajo_integracion` del sistema externo `SGF` junto con todos los `snapshots_datos_externos` que produjo. Por cada snapshot, el sistema SHALL incluir los datos normalizados disponibles en `payload_normalizado` (proveedor, monto, estado SGF, folio de egreso, número, período, fecha SII, observaciones), usando un valor vacío explícito para cualquier campo no capturado en ese snapshot. Cuando exista un `caso_pago_proveedor` cuyo `sgf_id` coincida con la `referencia_externa` del snapshot, el sistema SHALL incluir su id, el estado actual de su workflow interno, y un indicador `listo_para_egreso` que sea verdadero únicamente cuando ese caso tiene su tipo de proceso de pago clasificado, al menos un registro contable CGU (Traspaso), todos los ítems obligatorios de su checklist documental con un documento vinculado, y un `Proveedor` identificado. El sistema SHALL además incluir un resumen agregado de la corrida con el monto total de los snapshots producidos, la cantidad de proveedores identificados (con `Proveedor` coincidente por RUT) versus no identificados, y la cantidad de casos listos versus pendientes para Asignar Egreso.

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

#### Scenario: Un caso con toda su preparación completa se marca listo
- **WHEN** un `caso_pago_proveedor` del detalle tiene tipo de proceso de pago clasificado, al menos un Traspaso registrado, todos los ítems obligatorios de su checklist con documento vinculado, y `Proveedor` identificado
- **THEN** su `listo_para_egreso` es `true`

#### Scenario: Un caso con preparación incompleta no se marca listo
- **WHEN** a un `caso_pago_proveedor` del detalle le falta al menos uno de: tipo de proceso clasificado, Traspaso registrado, algún ítem obligatorio del checklist con documento, o `Proveedor` identificado
- **THEN** su `listo_para_egreso` es `false`

#### Scenario: El resumen incluye la cantidad de casos listos y pendientes
- **WHEN** un usuario autenticado abre el detalle de una importación SGF con casos vinculados
- **THEN** la respuesta incluye la cantidad de casos con `listo_para_egreso` verdadero y la cantidad con `listo_para_egreso` falso

