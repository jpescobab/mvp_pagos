## ADDED Requirements

### Requirement: Administrar roles y sus permisos
El sistema SHALL exponer, gobernado por el permiso `roles.administrar`, un CRUD de roles: listar roles con su conteo de usuarios asignados y de permisos, crear un rol con nombre y un checklist de permisos existentes agrupados por módulo, editar el nombre y los permisos de un rol existente, y eliminar un rol. Los roles core `superadmin` y `admin` SHALL NOT poder eliminarse, y un rol con usuarios asignados SHALL NOT poder eliminarse.

#### Scenario: Listar roles con conteos
- **WHEN** un usuario con el permiso `roles.administrar` visita la página de roles
- **THEN** la respuesta incluye cada rol con su nombre, la cantidad de usuarios que lo tienen asignado y la cantidad de permisos que le pertenecen

#### Scenario: Crear un rol nuevo
- **WHEN** un usuario con el permiso `roles.administrar` crea un rol con nombre y un conjunto de permisos existentes
- **THEN** el rol se crea con esos permisos asignados
- **AND** se registra la creación mediante `AuditLogger`

#### Scenario: Editar los permisos de un rol
- **WHEN** un usuario con el permiso `roles.administrar` actualiza el nombre o el conjunto de permisos de un rol existente
- **THEN** el rol queda con el nombre y los permisos actualizados
- **AND** se registra la edición mediante `AuditLogger`

#### Scenario: Intentar eliminar un rol core
- **WHEN** un usuario con el permiso `roles.administrar` intenta eliminar el rol `superadmin` o `admin`
- **THEN** el sistema bloquea la eliminación

#### Scenario: Intentar eliminar un rol con usuarios asignados
- **WHEN** un usuario con el permiso `roles.administrar` intenta eliminar un rol que tiene al menos un usuario asignado
- **THEN** el sistema bloquea la eliminación

#### Scenario: Eliminar un rol sin usuarios asignados
- **WHEN** un usuario con el permiso `roles.administrar` elimina un rol que no es core y no tiene usuarios asignados
- **THEN** el rol se elimina
- **AND** se registra la eliminación mediante `AuditLogger`

#### Scenario: Usuario sin permiso intenta administrar roles
- **WHEN** un usuario sin el permiso `roles.administrar` intenta listar, crear, editar o eliminar un rol
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

### Requirement: Reasignar roles a un usuario existente
El sistema SHALL permitir, gobernado por el permiso `usuarios.asignar_roles`, cambiar el conjunto de roles de un usuario institucional ya creado, sin necesidad de recrearlo.

#### Scenario: Reasignar roles desde la edición de usuario
- **WHEN** un usuario con el permiso `usuarios.asignar_roles` cambia el conjunto de roles de un usuario existente
- **THEN** el usuario queda con los roles actualizados
- **AND** se registra el cambio mediante `AuditLogger`

#### Scenario: Usuario sin permiso intenta reasignar roles
- **WHEN** un usuario sin el permiso `usuarios.asignar_roles` intenta cambiar los roles de un usuario existente
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`
