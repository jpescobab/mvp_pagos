## ADDED Requirements

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
