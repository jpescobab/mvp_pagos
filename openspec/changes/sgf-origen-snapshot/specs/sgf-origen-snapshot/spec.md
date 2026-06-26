## ADDED Requirements

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
