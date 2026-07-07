## ADDED Requirements

### Requirement: Vincular varios documentos a un mismo snapshot de datos externos
El sistema SHALL permitir vincular varios documentos del expediente (`Documento`) a un mismo `snapshot_datos_externo` mediante una tabla de unión (`snapshots_datos_externos_documentos`), independiente del `vinculable` polimórfico único que ya usa `snapshot_datos_externo` para su entidad interna asociada.

#### Scenario: Un snapshot con varios documentos entregados por el sistema externo
- **WHEN** un `snapshot_datos_externo` se genera a partir de datos que incluyen uno o más documentos
- **THEN** cada documento se crea o resuelve como `Documento`/`VersionDocumento` del expediente
- **AND** se crea un registro en `snapshots_datos_externos_documentos` que vincula cada documento a ese `snapshot_datos_externo`

#### Scenario: Un snapshot sin documentos asociados
- **WHEN** un `snapshot_datos_externo` se genera a partir de datos que no incluyen ningún documento
- **THEN** no se crea ningún registro en `snapshots_datos_externos_documentos` para ese snapshot
