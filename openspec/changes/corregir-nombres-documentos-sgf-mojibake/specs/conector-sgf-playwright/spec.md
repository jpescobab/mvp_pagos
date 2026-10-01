## ADDED Requirements

### Requirement: Nombres de archivo de documentos SGF con codificación correcta
El sistema SHALL reparar el mojibake (UTF-8 interpretado como Latin-1) en los nombres de archivo de los documentos descargados desde SGF antes de usarlos como nombre en disco, `ruta_archivo`, `nombre_archivo` y título del documento. La reparación SHALL aplicarse solo cuando el nombre contenga secuencias de mojibake y su re-decodificación produzca UTF-8 válido y no contenga bytes ya degradados (secuencias `Ã¿` o `Â¿`, cuyo carácter original no es recuperable); en cualquier otro caso el nombre SHALL conservarse intacto. El payload crudo del snapshot SGF SHALL permanecer sin modificar.

#### Scenario: Nombre con mojibake en la tabla de documentos de SGF
- **WHEN** el conector descarga un documento cuyo nombre llega como `CT NÂ°957_433 CONST.EHG.pdf`
- **THEN** el archivo se guarda como `CT N°957_433 CONST.EHG.pdf`
- **AND** el título, `nombre_archivo` y `ruta_archivo` del documento registrado usan ese nombre reparado

#### Scenario: Nombre legítimo sin mojibake
- **WHEN** el conector descarga un documento cuyo nombre no contiene secuencias de mojibake (por ejemplo `Garantía.pdf`)
- **THEN** el nombre se conserva exactamente igual

#### Scenario: Nombre con bytes ya degradados
- **WHEN** el conector descarga un documento cuyo nombre contiene `Ã¿` (por ejemplo `PASAJES_AÃ¿REOS.pdf`, donde el carácter original ya llegó degradado)
- **THEN** el nombre se conserva intacto en lugar de reemplazarlo por un carácter equivocado
- **AND** el comando de reparación lo reporta como irreparable automáticamente para revisión humana

#### Scenario: El payload crudo del snapshot no se altera
- **WHEN** el backend repara el nombre de un documento al registrarlo
- **THEN** el payload crudo del snapshot conserva el nombre tal como lo entregó el conector

### Requirement: Reimportación idempotente con nombres reparados
La reimportación de un caso SGF SHALL NOT duplicar un documento ya registrado, aunque el registro previo tenga la ruta con el nombre corrupto y la nueva importación entregue el nombre reparado (o viceversa).

#### Scenario: Registro previo con nombre corrupto y reimportación con nombre reparado
- **WHEN** un caso ya tiene un documento vinculado con ruta corrupta y se reimporta entregando la misma ruta reparada
- **THEN** no se crea un documento nuevo ni un vínculo adicional

### Requirement: Reparación de nombres de documentos SGF ya importados
El sistema SHALL ofrecer un comando Artisan idempotente que repare los nombres corruptos de documentos SGF ya importados, actualizando `titulo`, `nombre_archivo` y `ruta_archivo` y renombrando el archivo físico, sin modificar vínculos, hashes ni snapshots. El comando SHALL ofrecer modo `--dry-run`, SHALL NOT sobrescribir un archivo existente en el destino, y SHALL registrar auditoría de cada cambio aplicado.

#### Scenario: Ejecución en modo simulación
- **WHEN** se ejecuta el comando con `--dry-run`
- **THEN** lista los documentos que se repararían y no modifica base de datos ni archivos

#### Scenario: Reparación de un documento existente
- **WHEN** se ejecuta el comando sobre un documento con nombre corrupto cuyo archivo físico existe
- **THEN** el título, `nombre_archivo` y `ruta_archivo` quedan reparados, el archivo se renombra y se registra auditoría
- **AND** los vínculos del documento y su hash no cambian

#### Scenario: Conflicto de nombre en el destino
- **WHEN** el archivo con el nombre reparado ya existe en disco
- **THEN** el comando omite ese documento, lo reporta como conflicto y no sobrescribe nada

#### Scenario: Ejecución repetida
- **WHEN** el comando se ejecuta de nuevo tras una reparación exitosa
- **THEN** no encuentra nada que reparar y no modifica datos
