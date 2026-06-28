## ADDED Requirements

### Requirement: Permiso core para validar documentos del expediente
El sistema SHALL definir el permiso core `documentos.validar`, distinto de `documentos.gestionar`, para gobernar quién puede crear eventos de validación o rechazo sobre un documento del expediente.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** el permiso `documentos.validar` existe
- **AND** los roles `superadmin` y `admin` lo tienen asignado
