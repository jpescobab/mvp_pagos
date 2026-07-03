## ADDED Requirements

### Requirement: Permisos granulares para gestionar usuarios institucionales
El sistema SHALL definir los permisos `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.activar`, `usuarios.desactivar`, `usuarios.resetear_password` y `usuarios.asignar_roles`, en reemplazo del permiso único `usuarios.administrar`, para gobernar de forma auditable cada acción sobre usuarios institucionales por separado.

#### Scenario: Asignación inicial de los permisos
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** los siete permisos `usuarios.ver/crear/editar/activar/desactivar/resetear_password/asignar_roles` existen
- **AND** los roles `superadmin` y `admin` los tienen asignados
- **AND** el permiso `usuarios.administrar` ya no existe

## REMOVED Requirements

### Requirement: Permiso único usuarios.administrar
**Reason**: Reemplazado por una matriz de permisos granular (`usuarios.ver/crear/editar/activar/desactivar/resetear_password/asignar_roles`) que permite auditar y autorizar cada acción sobre usuarios de forma independiente, en vez de un único permiso que las cubría todas.
**Migration**: Cualquier verificación de `usuarios.administrar` debe reemplazarse por el permiso granular correspondiente a la acción concreta (ej. `usuarios.editar` para editar, `usuarios.activar` para activar). `UserPolicy` y los seeders de roles se actualizan en el mismo cambio (`listar-usuarios-institucionales`).
