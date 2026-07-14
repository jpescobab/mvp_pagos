## ADDED Requirements

### Requirement: Reclasificar el tipo de un documento vinculado
El sistema SHALL permitir cambiar el `tipo_documento_id` de un `Documento` ya vinculado activamente a un `Proceso`, a un usuario con el permiso `documentos.gestionar`, sin requerir volver a subir el archivo. El sistema SHALL rechazar la operación si el documento no está vinculado activamente al proceso indicado.

#### Scenario: Reclasificar un documento a un tipo distinto
- **WHEN** un usuario con `documentos.gestionar` cambia el tipo de un documento vinculado activamente a un proceso
- **THEN** el `tipo_documento_id` del `Documento` se actualiza
- **AND** la siguiente resolución del checklist documental de ese proceso refleja el nuevo tipo

#### Scenario: Usuario sin permiso intenta reclasificar
- **WHEN** un usuario sin `documentos.gestionar` intenta reclasificar un documento
- **THEN** el sistema bloquea la operación

#### Scenario: Reclasificar un documento no vinculado al proceso indicado
- **WHEN** se intenta reclasificar un documento que no tiene un `VinculoDocumento` activo con el proceso de la URL
- **THEN** el sistema rechaza la operación
