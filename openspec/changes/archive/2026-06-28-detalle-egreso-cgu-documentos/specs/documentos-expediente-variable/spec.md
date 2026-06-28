## MODIFIED Requirements

### Requirement: Subir y vincular un documento a un proceso vía HTTP
El sistema SHALL exponer endpoints autorizados (`documentos.gestionar`) para subir un archivo y vincularlo a un `Proceso` o a un `EgresoCgu`, creando un `Documento`, su primera `VersionDocumento` y un `VinculoDocumento` activo en una sola operación transaccional, sin que el modelo documental dependa de ningún módulo funcional específico.

#### Scenario: Subir un documento válido a un proceso
- **WHEN** un usuario con el permiso `documentos.gestionar` sube un archivo válido (tipo MIME permitido, tamaño dentro del límite) junto con un `tipo_documento_id` activo, para un `Proceso` existente
- **THEN** se crea un `Documento` con su `VersionDocumento` número 1 y un `VinculoDocumento` activo asociado a ese `Proceso`
- **AND** el archivo se almacena en un disco no público

#### Scenario: Subir un documento válido a un egreso CGU
- **WHEN** un usuario con el permiso `documentos.gestionar` sube un archivo válido junto con un `tipo_documento_id` activo, para un `EgresoCgu` existente
- **THEN** se crea un `Documento` con su `VersionDocumento` número 1 y un `VinculoDocumento` activo asociado a ese `EgresoCgu`
- **AND** el archivo se almacena en un disco no público

#### Scenario: Rechazar un archivo inválido
- **WHEN** se intenta subir un archivo con un tipo MIME no permitido o que excede el tamaño máximo
- **THEN** el sistema rechaza la operación sin crear ningún registro

#### Scenario: Usuario sin permiso intenta subir
- **WHEN** un usuario sin el permiso `documentos.gestionar` intenta subir un documento, a un `Proceso` o a un `EgresoCgu`
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

### Requirement: Listar y descargar los documentos vinculados a un proceso
El sistema SHALL incluir los documentos vinculados activos de un `Proceso` o de un `EgresoCgu` (tipo, nombre de archivo, estado vigente) en la misma respuesta que expone su detalle, y SHALL exponer un endpoint de descarga protegido por autenticación, sin URLs públicas directas al archivo.

#### Scenario: Ver documentos vinculados en el detalle de un proceso
- **WHEN** un usuario abre el detalle de un proceso (de cualquier módulo) que tiene documentos vinculados activos
- **THEN** la respuesta incluye la lista de esos documentos junto al checklist

#### Scenario: Ver documentos vinculados en el detalle de un egreso CGU
- **WHEN** un usuario abre el detalle de un egreso CGU que tiene documentos vinculados activos
- **THEN** la respuesta incluye la lista de esos documentos

#### Scenario: Descargar un documento vinculado
- **WHEN** un usuario autenticado solicita la descarga de un documento vinculado a un proceso o a un egreso CGU
- **THEN** el sistema sirve el archivo desde el disco privado
- **AND** un usuario no autenticado no puede acceder al archivo

### Requirement: Desvincular un documento sin perder su historial
El sistema SHALL permitir desvincular un documento de un proceso o de un egreso CGU marcando su `VinculoDocumento` como inactivo, sin eliminar el `Documento`, sus `VersionDocumento` ni su historial de validaciones.

#### Scenario: Desvincular un documento de un proceso
- **WHEN** un usuario con el permiso `documentos.gestionar` desvincula un documento de un proceso
- **THEN** el `VinculoDocumento` correspondiente queda `activo = false`
- **AND** el `Documento` y sus versiones permanecen en la base de datos

#### Scenario: Desvincular un documento de un egreso CGU
- **WHEN** un usuario con el permiso `documentos.gestionar` desvincula un documento de un egreso CGU
- **THEN** el `VinculoDocumento` correspondiente queda `activo = false`
- **AND** el `Documento` y sus versiones permanecen en la base de datos
