## Why

Los roles y permisos (Spatie Laravel Permission) solo existen hoy vía `database/seeders/RolesAndPermissionsSeeder.php`: no hay controlador, ruta ni página que permita al superadmin crear un rol, ajustar sus permisos, o cambiarle los roles a un usuario ya creado (la asignación de roles solo ocurre una vez, al crear el usuario). Cualquier ajuste de gobierno de acceso hoy requiere una migración/seeder y un despliegue, lo que contradice el propósito de "capa de control" institucional que gobierna el propio sistema. `app/Policies/RolePolicy.php` y el permiso `roles.administrar` ya existen esperando una UI que los use.

## What Changes

- **Listado de roles**: página que lista los roles existentes con conteo de usuarios asignados y conteo de permisos.
- **Crear rol**: formulario con nombre y checklist de permisos disponibles, agrupados por módulo (mismo agrupamiento visual del sidebar: General, Administración, Pago de Proveedores, Adquisiciones, Maestros, Reportabilidad, Integraciones).
- **Editar rol**: ajustar el nombre y el checklist de permisos de un rol existente.
- **Eliminar rol**: solo si no tiene usuarios asignados; los roles core `superadmin` y `admin` no se pueden eliminar.
- **Reasignar roles a un usuario existente**: nueva acción en la edición de usuario para cambiar sus roles después de creado (hoy solo se asignan al crear).
- **Auditoría**: crear/editar/eliminar un rol y reasignar roles a un usuario quedan registrados vía `AuditLogger`, igual que las acciones existentes de usuarios.
- **Sidebar**: nueva entrada "Roles y Permisos" en la sección Administración.
- **BREAKING** (ninguno): no se elimina ni renombra ningún permiso o rol existente; `roles.administrar` pasa de estar definido-sin-uso a gobernar las nuevas rutas.

## Capabilities

### New Capabilities
(ninguna — todo es evolución de la capability de seguridad existente)

### Modified Capabilities
- `seguridad-auditoria`: se agregan requirements para el CRUD de roles gobernado por `roles.administrar` (listar/crear/editar/eliminar con las guardas de roles core y roles con usuarios asignados), y para la reasignación de roles a un usuario existente (que se integra al requirement ya existente "Permisos granulares para gestionar usuarios institucionales" agregando la acción `usuarios.asignar_roles` fuera del flujo de creación).

## Impact

- Backend: nuevo `app/Http/Controllers/Seguridad/RoleController.php`, Form Requests de validación, uso de `AuditLogger` para las nuevas acciones. Posible ajuste en `GestionUsuariosService` (o servicio nuevo `GestionRolesService`) para reasignar roles a un usuario existente.
- Frontend: nuevas páginas `resources/js/pages/seguridad/roles/{index,create,edit}.tsx`, ajuste en `resources/js/pages/seguridad/usuarios/edit.tsx` para la reasignación de roles, nueva entrada en `resources/js/components/app-sidebar.tsx`.
- Rutas: nuevas rutas en `routes/seguridad.php` gobernadas por `roles.administrar` (y `usuarios.asignar_roles` para la reasignación).
- Tests: cobertura Pest para el CRUD de roles (incluyendo las guardas de eliminación) y para la reasignación de roles a un usuario existente.
- Sin cambios de esquema de base de datos (las tablas de Spatie Permission ya existen). Fuera de alcance: sistema de permisos dinámico fuera de Spatie, eliminar los roles core `superadmin`/`admin`.
