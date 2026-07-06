## ADDED Requirements

### Requirement: Permiso dedicado para disparar la importación manual de indicadores económicos
El sistema SHALL definir el permiso `indicadores.importar`, distinto de la visibilidad de la página de indicadores económicos (que sigue abierta a cualquier usuario autenticado), para gobernar quién puede disparar manualmente la importación mensual de indicadores económicos.

#### Scenario: Asignación inicial del permiso
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** el permiso `indicadores.importar` existe
- **AND** los roles `superadmin` y `admin` lo tienen asignado
