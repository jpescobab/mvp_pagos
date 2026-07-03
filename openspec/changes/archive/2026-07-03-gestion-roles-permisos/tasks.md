## 1. Backend — roles

- [x] 1.1 Crear `app/Http/Controllers/Seguridad/RoleController.php` con `index()` (lista roles con `withCount('users')` y `withCount('permissions')`, filtro de búsqueda por nombre, paginación), `create()`/`store()`, `edit()`/`update()`, `destroy()`. Usar `Gate`/`authorize()` contra `RolePolicy` (ya existe) en cada acción.
- [x] 1.2 Form Requests `app/Http/Requests/Seguridad/StoreRoleRequest.php` y `UpdateRoleRequest.php`: validan `name` (único, ignorando el propio rol al editar) y `permissions` (array de IDs existentes en la tabla `permissions`).
- [x] 1.3 Servicio `app/Services/Seguridad/GestionRolesService.php` (nuevo) con métodos `crear(array $datos): Role`, `editar(Role $rol, array $datos): void`, `eliminar(Role $rol): void`; cada uno usa `AuditLogger` (acciones `crear_rol`, `editar_rol`, `eliminar_rol`) dentro de una transacción. `eliminar()` lanza `RuntimeException` si el rol es `superadmin`/`admin` o si tiene usuarios asignados (`$rol->users()->exists()`); el controlador traduce esa excepción a un error de validación/flash, igual que el patrón ya usado para `desactivar()` en `UserController`.
- [x] 1.4 Endpoint auxiliar (o prop incluida en `index`/`create`/`edit`) que exponga el catálogo de permisos agrupado por módulo: mapear el prefijo de cada `Permission->name` (antes del primer `.`) a las mismas etiquetas de grupo del sidebar (General, Administración, Pago de Proveedores, Adquisiciones, Maestros, Reportabilidad, Integraciones) mediante un mapa fijo en el backend.
- [x] 1.5 Rutas en `routes/seguridad.php`: `GET /roles` (`roles.index`), `GET /roles/create` (`roles.create`), `POST /roles` (`roles.store`), `GET /roles/{role}/editar` (`roles.edit`), `PATCH /roles/{role}` (`roles.update`), `DELETE /roles/{role}` (`roles.destroy`), todas bajo `middleware(['auth'])`.

## 2. Backend — reasignar roles de usuario

- [x] 2.1 Agregar `asignarRoles(User $usuario, array $roles): void` a `app/Services/Seguridad/GestionUsuariosService.php`: sincroniza roles (`$usuario->syncRoles($roles)`), bloquea con `RuntimeException` si el usuario es el último Administrador del Sistema activo y se le están quitando los roles `admin`/`superadmin` (reutilizar/generalizar la lógica privada `esUltimoAdministradorActivo` ya existente), y registra el cambio con `AuditLogger` (acción `reasignar_roles_usuario`, before/after con los IDs de roles).
- [x] 2.2 Nueva ruta `PATCH /usuarios/{usuario}/roles` (`usuarios.roles.update`) en `routes/seguridad.php`, gobernada por el permiso `usuarios.asignar_roles` (autorizar en el controlador o policy existente de `UserController`).
- [x] 2.3 Nuevo método `UserController::actualizarRoles(Request $request, User $usuario)` que valida el array de roles (IDs existentes) y delega a `GestionUsuariosService::asignarRoles()`.

## 3. Frontend — roles

- [x] 3.1 `resources/js/pages/seguridad/roles/index.tsx`: tabla de roles con nombre, conteo de usuarios, conteo de permisos, acciones editar/eliminar (el botón eliminar deshabilitado con tooltip explicativo si el rol es core o tiene usuarios asignados), botón "Crear rol".
- [x] 3.2 `resources/js/pages/seguridad/roles/create.tsx` y `edit.tsx`: formulario con nombre y checklist de permisos agrupado por módulo (`Collapsible` o acordeón simple por grupo, reusando el mismo agrupamiento visual del sidebar), usando `useForm`/Wayfinder.
- [x] 3.3 Regenerar Wayfinder (`php artisan wayfinder:generate --with-form`) tras crear las rutas de roles.

## 4. Frontend — reasignar roles de usuario

- [x] 4.1 En `resources/js/pages/seguridad/usuarios/edit.tsx`, agregar sección/checklist para cambiar los roles del usuario (visible solo si el usuario autenticado tiene `usuarios.asignar_roles`), con su propio submit hacia `usuarios.roles.update` independiente del formulario de datos personales.

## 5. Sidebar

- [x] 5.1 `resources/js/components/app-sidebar.tsx`: agregar "Roles y Permisos" al grupo "Administración", enlazando a `roles.index()`.

## 6. Pruebas

- [x] 6.1 `tests/Feature/Seguridad/GestionRolesTest.php`: listar roles con conteos, crear rol con permisos, editar permisos de un rol, eliminar rol sin usuarios (éxito), eliminar rol con usuarios asignados (bloqueado), eliminar rol core `superadmin`/`admin` (bloqueado), usuario sin `roles.administrar` recibe 403 en cada acción, cada mutación exitosa deja un registro en `audit_logs`.
- [x] 6.2 `tests/Feature/Seguridad/ReasignarRolesUsuarioTest.php`: reasignar roles de un usuario existente (éxito, con registro en `audit_logs`), bloquear si se intenta quitar el rol de administrador al último Administrador del Sistema activo, usuario sin `usuarios.asignar_roles` recibe 403.

## 7. Validación

- [x] 7.1 `vendor/bin/pint --dirty --format agent` sobre PHP tocado.
- [x] 7.2 `composer test` (lint + phpstan + Pest completo).
- [x] 7.3 `npm run lint:check` y `npm run types:check`.
- [x] 7.4 Verificación en navegador: listar/crear/editar/eliminar rol (incluyendo los bloqueos de rol core y rol con usuarios), reasignar roles desde la edición de un usuario, nueva entrada del sidebar visible solo con el permiso correspondiente.
