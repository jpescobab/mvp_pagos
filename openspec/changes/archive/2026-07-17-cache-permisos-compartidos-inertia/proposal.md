## Why

`HandleInertiaRequests::permisosCompartidos()` corre en cada request Inertia autenticado (toda página, no solo `/dashboard`). Para un usuario sin rol `superadmin`, `$user->getAllPermissions()->pluck('name')` hidrata una instancia Eloquent de `Permission` por cada permiso efectivo del usuario (hasta 37 de 38 para el rol `admin`, verificado contra `role_has_permissions`), en cada carga de página, sin caché propia — el caché interno de Spatie (TTL 24h) cachea el grafo rol→permiso, no esta lista final ya calculada por usuario. Para `superadmin`, `Permission::query()->orderBy('name')->pluck('name')` evita hidratar modelos pero ejecuta una consulta sin caché en cada página, bypaseando también el caché de Spatie. Esto contribuye directamente al costo de Models/Queries que se observa en cada navegación autenticada, no solo en el dashboard.

## What Changes

- Envolver la resolución de permisos compartidos en un nuevo servicio `PermisosCompartidosResolver`, cacheada por usuario con TTL corto (5 min, consistente con `IndicadorEconomicoSelector`), preservando intactas ambas ramas de comportamiento (superadmin: todos los permisos existentes; resto: `getAllPermissions()` de sus roles).
- Invalidar explícitamente la entrada de un usuario cuando se reasignan sus roles (`GestionUsuariosService::asignarRoles`).
- Invalidar explícitamente la entrada de todos los usuarios de un rol cuando se editan los permisos de ese rol (`GestionRolesService::editar`).
- Sin cambios de comportamiento observable: mismas dos ramas, mismos valores; solo se evita recalcularlos (y rehidratar modelos) en cada request dentro del TTL.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `seguridad-auditoria`: el requirement "Los permisos compartidos al frontend reflejan el acceso efectivo del usuario" agrega que la lista se sirve desde caché por usuario, con invalidación explícita al reasignar los roles de un usuario o al editar los permisos de un rol.

## Impact

- `app/Services/Seguridad/PermisosCompartidosResolver.php` (nuevo).
- `app/Http/Middleware/HandleInertiaRequests.php`: delega en el nuevo resolver, elimina `permisosCompartidos()` y los imports que queden sin uso.
- `app/Services/Seguridad/GestionUsuariosService.php`: invalida la entrada del usuario en `asignarRoles()`.
- `app/Services/Seguridad/GestionRolesService.php`: invalida las entradas de los usuarios del rol en `editar()`.
- `tests/Feature/Seguridad/`: test nuevo para el resolver, casos nuevos en los tests existentes de reasignación de roles y gestión de roles; el test existente de permisos compartidos (`PermisosCompartidosInertiaTest.php`) queda sin cambios.
