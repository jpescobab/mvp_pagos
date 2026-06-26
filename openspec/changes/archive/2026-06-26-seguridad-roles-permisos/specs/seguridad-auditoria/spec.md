## ADDED Requirements

### Requirement: Controlar acceso por roles y permisos
El sistema SHALL validar permisos antes de ejecutar una acción autorizable, mediante roles y permisos gestionados con Spatie Permission. El rol `superadmin` SHALL tener acceso total sin necesidad de asignación de permisos individuales.

#### Scenario: Acción no permitida
- **WHEN** un usuario sin el permiso requerido intenta ejecutar una acción autorizable
- **THEN** el sistema bloquea la operación
- **AND** registra un evento en `security_audit_logs` con el usuario, la acción y el resultado

#### Scenario: Superadmin tiene acceso total
- **WHEN** un usuario con rol `superadmin` intenta ejecutar cualquier acción autorizable
- **THEN** el sistema permite la operación sin requerir un permiso específico asignado

### Requirement: Auditar acciones relevantes mediante un servicio genérico
El sistema SHALL proveer un servicio de auditoría (`AuditLogger`) capaz de registrar usuario, acción, entidad afectada, estado anterior, estado nuevo y metadata, reutilizable por cualquier dominio que necesite auditar cambios sensibles.

#### Scenario: Registrar una acción auditada genérica
- **WHEN** se invoca `AuditLogger::log()` con una acción, una entidad y los estados antes/después
- **THEN** se crea un registro en `audit_logs` con esos datos y el usuario autenticado

#### Scenario: Auditar cambio de estado de workflow (pendiente de conexión)
- **WHEN** un usuario ejecuta una transición de workflow mediante `WorkflowTransitionService`
- **THEN** se registra usuario, fecha, estado anterior, estado nuevo, comentario y metadata mediante `AuditLogger`
- **AND** esta conexión se implementa en la tarea de workflow-core, no en esta tarea
