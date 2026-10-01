## ADDED Requirements

### Requirement: Detectar archivos sueltos de un caso importado desde SGF
El sistema SHALL, para un `CasoPagoProveedor` (siempre tiene `sgf_id`, es la vía de importación desde SGF), identificar los archivos presentes en `storage/app/private/sgf-documentos/{sgfId}/` que no tienen ninguna fila `VersionDocumento` con esa misma ruta relativa.

#### Scenario: Existe un archivo sin registrar
- **WHEN** se consulta el detalle de un `CasoPagoProveedor` cuya carpeta `sgf-documentos/{sgfId}/` contiene un archivo sin `VersionDocumento` asociado
- **THEN** el sistema lo incluye en la lista de archivos sueltos disponibles para vincular

#### Scenario: Todos los archivos ya están registrados
- **WHEN** todos los archivos de la carpeta del caso ya tienen su `VersionDocumento` correspondiente
- **THEN** la lista de archivos sueltos disponibles está vacía

#### Scenario: La carpeta del caso no existe todavía
- **WHEN** se consulta un `CasoPagoProveedor` cuya carpeta `sgf-documentos/{sgfId}/` nunca se creó (ningún documento descargado aún)
- **THEN** el sistema devuelve la lista de archivos sueltos vacía, sin error

### Requirement: Vincular un archivo suelto a un ítem del checklist sin volver a subirlo
El sistema SHALL permitir, a un usuario con el permiso `documentos.gestionar`, vincular uno de los archivos sueltos detectados a un `Proceso` como evidencia de un tipo de documento, creando su `Documento`/`VersionDocumento`/`VinculoDocumento` a partir del archivo ya existente en disco (hash calculado del archivo existente, sin requerir una nueva subida).

#### Scenario: Vinculación exitosa
- **WHEN** un usuario con el permiso requerido elige un archivo suelto y un tipo de documento para un ítem pendiente del checklist
- **THEN** el sistema crea el `Documento`/`VersionDocumento`/`VinculoDocumento` correspondientes, y el archivo deja de aparecer en la lista de sueltos

### Requirement: La ruta de archivo se revalida en el servidor antes de vincular
El sistema SHALL rechazar cualquier solicitud de vinculación cuya ruta de archivo no forme parte, en el momento de la solicitud, del conjunto de archivos sueltos vigentes del caso correspondiente al `Proceso` indicado — nunca SHALL confiar en la ruta recibida del cliente sin revalidarla.

#### Scenario: Ruta fuera del conjunto vigente
- **WHEN** un usuario envía una ruta de archivo que no está en la lista de archivos sueltos vigentes del caso (por ejemplo, una ruta ya vinculada, inexistente, o de otro caso)
- **THEN** el sistema rechaza la operación sin crear ningún registro

### Requirement: La vinculación de un archivo suelto no altera el workflow del caso
El sistema SHALL tratar la vinculación de un archivo suelto como una operación puramente documental: SHALL NOT disparar ninguna transición del `Proceso`/workflow del `CasoPagoProveedor` como consecuencia de esa acción.

#### Scenario: Vincular un archivo no cambia el estado del caso
- **WHEN** un usuario vincula un archivo suelto a un ítem del checklist de un `CasoPagoProveedor`
- **THEN** el estado del `Proceso` de ese caso no cambia como consecuencia de esa acción
