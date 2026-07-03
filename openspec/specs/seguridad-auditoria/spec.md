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


### Requirement: Permisos granulares para gestionar usuarios institucionales
El sistema SHALL definir los permisos `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.activar`, `usuarios.desactivar`, `usuarios.resetear_password` y `usuarios.asignar_roles`, en reemplazo del permiso único `usuarios.administrar`, para gobernar de forma auditable cada acción sobre usuarios institucionales por separado.

#### Scenario: Asignación inicial de los permisos
- **WHEN** se ejecuta `RolesAndPermissionsSeeder`
- **THEN** los siete permisos `usuarios.ver/crear/editar/activar/desactivar/resetear_password/asignar_roles` existen
- **AND** los roles `superadmin` y `admin` los tienen asignados
- **AND** el permiso `usuarios.administrar` ya no existe


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
