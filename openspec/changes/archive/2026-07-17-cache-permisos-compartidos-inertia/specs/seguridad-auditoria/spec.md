## MODIFIED Requirements

### Requirement: Los permisos compartidos al frontend reflejan el acceso efectivo del usuario
El sistema SHALL compartir al frontend, en cada request Inertia (`auth.permissions`), la lista de permisos que refleja el acceso **efectivo** del usuario autenticado para condicionar la UI (ítems de sidebar, acciones visibles). Para un usuario con el rol `superadmin` —que bypassea todos los gates vía `Gate::before`— la lista SHALL contener todos los permisos existentes; para cualquier otro usuario, los permisos de sus roles (`getAllPermissions()`). Un usuario no autenticado recibe una lista vacía. Esta lista SHALL servirse desde caché por usuario con un TTL corto, invalidada explícitamente cuando cambian los roles del usuario o los permisos de alguno de sus roles.

#### Scenario: El superadmin recibe todos los permisos, incluidos los de módulos
- **WHEN** un usuario con rol `superadmin` carga cualquier página Inertia
- **THEN** `auth.permissions` incluye todos los permisos existentes (por ejemplo `pago_proveedores.revisar_finanzas` y `pago_proveedores.revisar_zonal`), aunque no estén asignados a sus roles
- **AND** la UI condicionada por permisos (sidebar, acciones) le muestra todas las opciones, en coherencia con su bypass de gates

#### Scenario: Un usuario con rol funcional recibe solo los permisos de sus roles
- **WHEN** un usuario con el rol `jefe_finanzas` (sin `superadmin`) carga una página Inertia
- **THEN** `auth.permissions` contiene `pago_proveedores.revisar_finanzas`
- **AND** no contiene `pago_proveedores.revisar_zonal`

#### Scenario: Servir los permisos compartidos desde caché en cargas sucesivas
- **WHEN** un usuario autenticado carga una segunda página Inertia dentro del TTL de la caché de permisos
- **THEN** el sistema retorna la lista de permisos cacheada sin recalcularla ni volver a resolver los roles/permisos del usuario

#### Scenario: Invalidar la caché al reasignar los roles de un usuario
- **WHEN** se reasignan los roles de un usuario
- **THEN** el sistema invalida la entrada de caché de permisos compartidos de ese usuario
- **AND** la siguiente carga de página de ese usuario refleja los permisos correspondientes a sus roles nuevos, sin esperar a que expire el TTL

#### Scenario: Invalidar la caché de todos los usuarios de un rol al editar sus permisos
- **WHEN** se editan los permisos de un rol que tiene usuarios asignados
- **THEN** el sistema invalida la entrada de caché de permisos compartidos de cada usuario asignado a ese rol
- **AND** la siguiente carga de página de esos usuarios refleja los permisos actualizados del rol, sin esperar a que expire el TTL
