## MODIFIED Requirements

### Requirement: Auditar acciones relevantes mediante un servicio genérico
El sistema SHALL proveer un servicio de auditoría (`AuditLogger`) capaz de registrar usuario, acción, entidad afectada, estado anterior, estado nuevo y metadata, reutilizable por cualquier dominio que necesite auditar cambios sensibles.

#### Scenario: Registrar una acción auditada genérica
- **WHEN** se invoca `AuditLogger::log()` con una acción, una entidad y los estados antes/después
- **THEN** se crea un registro en `audit_logs` con esos datos y el usuario autenticado

#### Scenario: Auditar cambio de estado de workflow
- **WHEN** `WorkflowTransitionService::execute()` ejecuta una transición de workflow
- **THEN** se registra usuario, fecha, estado anterior, estado nuevo, comentario y metadata mediante `AuditLogger`
