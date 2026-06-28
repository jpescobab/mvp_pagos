## MODIFIED Requirements

### Requirement: Versionar documentos con trazabilidad de validación
Todo documento cargado al expediente SHALL quedar asociado a un tipo documental, conservar todas sus versiones (`versiones_documento`) y registrar un historial inmutable de eventos de validación (`validaciones_documento`). El estado vigente de un documento es el del evento de validación más reciente.

#### Scenario: Subir una nueva versión de un documento
- **WHEN** se carga un archivo para un documento existente
- **THEN** se crea una nueva fila en `versiones_documento`
- **AND** las versiones anteriores no se eliminan

#### Scenario: Validar un documento
- **WHEN** un usuario autorizado valida o rechaza un documento
- **THEN** se registra un nuevo evento en `validaciones_documento` con el resultado y la observación
- **AND** el estado vigente del documento pasa a ser el de ese evento

#### Scenario: Validar o rechazar un documento vía HTTP requiere el permiso dedicado
- **WHEN** un usuario con el permiso `documentos.validar` envía una validación (`valido` o `rechazado`) para un documento vinculado a un proceso
- **THEN** se crea el evento en `validaciones_documento` con su usuario y fecha
- **AND** el checklist documental del proceso refleja ese estado en su siguiente resolución

#### Scenario: Rechazar un documento exige una observación
- **WHEN** se envía una validación con `estado: 'rechazado'` sin `observacion`
- **THEN** el sistema rechaza la operación sin crear ningún evento

#### Scenario: Usuario sin permiso intenta validar
- **WHEN** un usuario sin el permiso `documentos.validar` intenta validar o rechazar un documento
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`
