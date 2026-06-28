## ADDED Requirements

### Requirement: Permiso core para gestionar documentos del expediente
El sistema SHALL definir el permiso core `documentos.gestionar` (distinto de los permisos de módulos funcionales), para gobernar quién puede subir y desvincular documentos de un `Proceso`, dado que el expediente documental es infraestructura no desactivable y no pertenece a ningún módulo funcional específico.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** el permiso `documentos.gestionar` existe
- **AND** los roles `superadmin` y `admin` lo tienen asignado
