# Spec: sgf-origen-snapshot

## Purpose

Capa de evidencia para datos provenientes de SGF: captura y conserva snapshots inmutables de filas y documentos (payload crudo, normalizado, hash, fuente), sin gobernar workflow ni crear casos de pago. SGF es origen, no gobierno.

## Requirements

### Requirement: Registrar cada corrida de importación SGF
El sistema SHALL registrar cada corrida de importación de filas SGF en `importaciones_sgf`, incluyendo fuente, quién o qué la inició y su resultado, independientemente del mecanismo que haya obtenido las filas.

#### Scenario: Iniciar una corrida de importación
- **WHEN** se inicia una importación de filas SGF
- **THEN** se crea una `importacion_sgf` con su fuente y momento de inicio
- **AND** cada fila procesada en esa corrida queda asociada a esa importación

### Requirement: Conservar snapshot inmutable de cada fila SGF
El sistema SHALL conservar, por cada fila SGF importada, su payload original, un payload normalizado y el hash del contenido recibido, sin sobrescribir snapshots anteriores del mismo `sgf_id`.

#### Scenario: Importar una fila SGF
- **WHEN** se importa una fila SGF con ID, estado, grupo actual, observaciones, RUT y monto
- **THEN** se crea un `snapshot_sgf` con `payload_crudo`, `payload_normalizado` y el hash del contenido
- **AND** el `sgf_status` y el `sgf_current_group_raw` quedan guardados solo como referencia externa
- **AND** no se usa el estado SGF como workflow interno ni el grupo SGF como unidad interna

#### Scenario: Reimportar el mismo sgf_id crea un snapshot nuevo
- **WHEN** se vuelve a importar una fila SGF cuyo `sgf_id` ya tiene un snapshot previo
- **THEN** se crea un nuevo `snapshot_sgf`
- **AND** el snapshot anterior no se modifica ni se elimina

### Requirement: Conservar documentos SGF en el expediente documental
El sistema SHALL registrar los documentos que SGF entrega junto a una fila como documentos reales del expediente (`documentos`/`versiones_documento`), vinculados a su snapshot de origen mediante `snapshots_sgf_documentos`.

#### Scenario: Vincular un documento SGF a su snapshot
- **WHEN** una fila SGF importada incluye uno o más documentos
- **THEN** cada documento se crea como `Documento`/`VersionDocumento` del expediente
- **AND** se crea un `snapshot_sgf_documento` que vincula ese documento a su `snapshot_sgf`

### Requirement: Mostrar el historial de snapshots SGF en el detalle del caso de pago
El sistema SHALL exponer, en el detalle de un `caso_pago_proveedor`, el historial completo de `snapshots_sgf` cuyo `sgf_id` coincide con el del caso, ordenado del más reciente al más antiguo, sin permiso adicional al ya requerido para ver el detalle del caso.

#### Scenario: Ver el historial de snapshots de un caso con varias importaciones
- **WHEN** un usuario autorizado abre el detalle de un `caso_pago_proveedor` cuyo `sgf_id` tiene más de un `snapshot_sgf`
- **THEN** la respuesta incluye todos los snapshots de ese `sgf_id`, ordenados del más reciente al más antiguo
- **AND** cada snapshot incluye su fecha de captura, hash y la fuente de su `importacion_sgf`

#### Scenario: Ver el detalle de un snapshot
- **WHEN** un usuario expande un snapshot del historial
- **THEN** la página muestra su `payload_crudo` y `payload_normalizado` completos
