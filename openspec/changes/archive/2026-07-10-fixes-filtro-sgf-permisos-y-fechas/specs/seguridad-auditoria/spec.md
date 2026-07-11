## ADDED Requirements

### Requirement: Los permisos compartidos al frontend reflejan el acceso efectivo del usuario
El sistema SHALL compartir al frontend, en cada request Inertia (`auth.permissions`), la lista de permisos que refleja el acceso **efectivo** del usuario autenticado para condicionar la UI (ítems de sidebar, acciones visibles). Para un usuario con el rol `superadmin` —que bypassea todos los gates vía `Gate::before`— la lista SHALL contener todos los permisos existentes; para cualquier otro usuario, los permisos de sus roles (`getAllPermissions()`). Un usuario no autenticado recibe una lista vacía.

#### Scenario: El superadmin recibe todos los permisos, incluidos los de módulos
- **WHEN** un usuario con rol `superadmin` carga cualquier página Inertia
- **THEN** `auth.permissions` incluye todos los permisos existentes (por ejemplo `pago_proveedores.revisar_finanzas` y `pago_proveedores.revisar_zonal`), aunque no estén asignados a sus roles
- **AND** la UI condicionada por permisos (sidebar, acciones) le muestra todas las opciones, en coherencia con su bypass de gates

#### Scenario: Un usuario con rol funcional recibe solo los permisos de sus roles
- **WHEN** un usuario con el rol `jefe_finanzas` (sin `superadmin`) carga una página Inertia
- **THEN** `auth.permissions` contiene `pago_proveedores.revisar_finanzas`
- **AND** no contiene `pago_proveedores.revisar_zonal`
