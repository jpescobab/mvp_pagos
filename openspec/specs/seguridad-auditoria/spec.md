# Spec: seguridad-auditoria

## Purpose

Controla el acceso al sistema mediante roles y permisos (Spatie Permission) y provee un servicio de auditoría genérico y reutilizable para que cualquier dominio registre cambios sensibles.

## Requirements

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

#### Scenario: Auditar cambio de estado de workflow
- **WHEN** `TransicionWorkflowService::execute()` ejecuta una transición de workflow
- **THEN** se registra usuario, fecha, estado anterior, estado nuevo, comentario y metadata mediante `AuditLogger`


### Requirement: Permiso dedicado para vincular adquisiciones a casos de pago
El sistema SHALL definir el permiso `pago_proveedores.vincular_adquisicion`, distinto de los permisos de gestión del ciclo de vida de Adquisiciones (`adquisiciones.publicar`, `adquisiciones.adjudicar`, `adquisiciones.anular`), para gobernar quién puede crear o quitar el vínculo entre un `caso_pago_proveedor` y un `proceso_adquisicion`.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta el seeder de roles y permisos del módulo Pago de Proveedores
- **THEN** el permiso `pago_proveedores.vincular_adquisicion` existe
- **AND** el rol `admin` lo tiene asignado


### Requirement: Permiso core para gestionar documentos del expediente
El sistema SHALL definir el permiso core `documentos.gestionar` (distinto de los permisos de módulos funcionales), para gobernar quién puede subir y desvincular documentos de un `Proceso`, dado que el expediente documental es infraestructura no desactivable y no pertenece a ningún módulo funcional específico.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** el permiso `documentos.gestionar` existe
- **AND** los roles `superadmin` y `admin` lo tienen asignado


### Requirement: Permiso core para validar documentos del expediente
El sistema SHALL definir el permiso core `documentos.validar`, distinto de `documentos.gestionar`, para gobernar quién puede crear eventos de validación o rechazo sobre un documento del expediente.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** el permiso `documentos.validar` existe
- **AND** los roles `superadmin` y `admin` lo tienen asignado


### Requirement: Visualizar el historial de auditoría
El sistema SHALL exponer una página autorizada (`auditoria.ver`) que liste los `audit_logs` paginados, ordenados del más reciente al más antiguo, con el usuario, la acción, la entidad afectada y los estados antes/después de cada registro.

#### Scenario: Listar el historial de auditoría
- **WHEN** un usuario con el permiso `auditoria.ver` visita la página de auditoría
- **THEN** la respuesta incluye los `audit_logs` paginados con usuario, acción, entidad afectada y fecha

#### Scenario: Ver el detalle de un registro
- **WHEN** un usuario expande un registro del historial de auditoría
- **THEN** la página muestra sus estados `before`, `after` y `metadata`

#### Scenario: Usuario sin permiso intenta ver la auditoría
- **WHEN** un usuario sin el permiso `auditoria.ver` intenta acceder a la página de auditoría
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

### Requirement: Permiso core para visualizar el historial de auditoría
El sistema SHALL definir el permiso core `auditoria.ver` (distinto de los permisos de módulos funcionales), para gobernar quién puede consultar `audit_logs`, dado que la auditoría es infraestructura no desactivable y transversal a todos los dominios.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** el permiso `auditoria.ver` existe
- **AND** los roles `superadmin` y `admin` lo tienen asignado
