## 1. Esquema de datos

- [x] 1.1 Migración `add_active_last_login_at_must_change_password_to_users_table`: agregar `active` (bool, default true), `last_login_at` (timestamp nullable), `must_change_password` (bool, default false) a `users`.
- [x] 1.2 Migración `add_cargo_unidad_to_funcionarios_table`: agregar `cargo` (string nullable), `unidad` (string nullable) a `funcionarios`.
- [x] 1.3 `User`: agregar `funcionario(): HasOne<Funcionario>`, casts `active` => bool y `must_change_password` => bool, agregar `active`/`must_change_password` a los atributos documentados en el PHPDoc.
- [x] 1.4 `Funcionario`: agregar `cargo` y `unidad` a `$fillable`.
- [x] 1.5 Actualizar `UserFactory` (si aplica) para que los usuarios de prueba nazcan con `active = true` por defecto.

## 2. Permisos y autorización

- [x] 2.1 `RolesAndPermissionsSeeder`: reemplazar `usuarios.administrar` por `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.activar`, `usuarios.desactivar`, `usuarios.resetear_password`, `usuarios.asignar_roles`; asignar los siete a `superadmin` y `admin`; eliminar explícitamente el permiso `usuarios.administrar` si existe en la tabla `permissions`.
- [x] 2.2 Actualizar `tests/Feature/Seguridad/RolesAndPermissionsSeederTest.php` con la nueva lista de permisos esperada.
- [x] 2.3 `UserPolicy`: `viewAny`/`view` → `usuarios.ver`; `create` → `usuarios.crear`; `update` → `usuarios.editar`; `delete` → `false` siempre (eliminación física prohibida); agregar `activate`, `deactivate`, `resetPassword`, `assignRoles` mapeados a sus permisos granulares.

## 3. Backend — listado y acciones

- [x] 3.1 Crear `app/Services/Seguridad/GestionUsuariosService.php`: `activar(User $usuario)`, `desactivar(User $actor, User $usuario)` (valida auto-desactivación y último admin/superadmin activo), `resetearPassword(User $usuario): string` (genera y retorna contraseña temporal, hashea, marca `must_change_password`), cada método registra el evento correspondiente vía `AuditLogger`.
- [x] 3.2 Crear `app/Http/Controllers/Seguridad/UserController.php` con `index()`: valida `usuarios.ver` (Policy), aplica búsqueda (nombre/email/rut vía `whereHas('funcionario', ...)` u `orWhere`), filtros (estado, rol, jurisdicción vía `funcionario.cfinanciero.jurisdiccion`, centro financiero, centro de costo), orden (nombre/email/estado/último acceso/fecha de creación; default activos primero + nombre asc), paginación (`per_page` 15/25/50/100, default 15), carga `roles`, `funcionario.cfinanciero.jurisdiccion`, `funcionario.ccosto`.
- [x] 3.3 Agregar a `UserController`: `activar(User $usuario)` (PATCH), `desactivar(User $usuario)` (PATCH), `resetPassword(User $usuario)` (POST) — cada uno autoriza contra la Policy correspondiente, delega en `GestionUsuariosService`, y redirige/responde conservando filtros/página/orden (Inertia: `back()` o redirect con query string preservada).
- [x] 3.4 Crear `app/Http/Resources/Seguridad/UserResource.php`: expone `id, name, email, rut, cargo, unidad, active, last_login_at, created_at, roles, jurisdiccion, centro_financiero, centro_costo` (todos nullable donde no haya `Funcionario`), nunca expone `password` ni hashes.
- [x] 3.5 `UserController@index`: enviar a Inertia `users` (paginado), `filters` (valores actuales), `catalogs` (roles, jurisdicciones, centros financieros, centros de costo activos), `permissions` (`can_create_user`, `can_view_user`, `can_edit_user`, `can_activate_user`, `can_deactivate_user`, `can_reset_password`, `can_assign_roles`) calculadas server-side con la Policy.
- [x] 3.6 Listener del evento `Illuminate\Auth\Events\Login` de Fortify que actualiza `users.last_login_at = now()`.

## 4. Rutas

- [x] 4.1 En `routes/seguridad.php`, agregar bajo `middleware(['auth'])`: `GET /usuarios` (`usuarios.index`), `PATCH /usuarios/{user}/activar` (`usuarios.activar`), `PATCH /usuarios/{user}/desactivar` (`usuarios.desactivar`), `POST /usuarios/{user}/reset-password` (`usuarios.reset-password`).
- [x] 4.2 Regenerar rutas/acciones Wayfinder (`php artisan wayfinder:generate` o build de Vite) para que `resources/js/actions`/`resources/js/routes` incluyan las nuevas rutas.

## 5. Frontend

- [x] 5.1 Agregar tipos en `resources/js/types/seguridad.ts`: `UsuarioListado`, `FiltrosUsuarios`, `CatalogosUsuarios`, `PermisosUsuarios` (reutilizar `Paginated<T>` existente).
- [x] 5.2 Crear `resources/js/pages/seguridad/usuarios/index.tsx`: título "Usuarios", subtítulo "Administración de usuarios institucionales", botón "Nuevo usuario" condicionado a `permissions.can_create_user` (enlaza a `/usuarios/create`, sin implementar esa página en este cambio).
- [x] 5.3 Crear `resources/js/components/seguridad/user-filters.tsx`: búsqueda general + filtros de estado/rol/jurisdicción/centro financiero/centro de costo, con botón "Limpiar filtros".
- [x] 5.4 Crear `resources/js/components/seguridad/users-table.tsx`: tabla desktop con todas las columnas; oculta columnas institucionales secundarias en pantalla mediana; vista de cards en móvil (nombre, email, estado, roles, acciones) sin perder datos (el resto queda accesible solo cuando exista Ver detalle).
- [x] 5.5 Crear `resources/js/components/seguridad/user-status-badge.tsx` (activo/inactivo).
- [x] 5.6 Crear `resources/js/components/seguridad/user-actions-menu.tsx`: menú desplegable por fila; Activar (solo si inactivo y `can_activate_user`) y Desactivar (solo si activo y `can_deactivate_user`) con diálogo de confirmación; Resetear contraseña (`can_reset_password`) con diálogo de confirmación y modal que muestra la contraseña temporal una única vez; Ver detalle/Editar usuario/Asignar roles visibles-pero-deshabilitadas con tooltip "Disponible próximamente" cuando el permiso respectivo está presente.
- [x] 5.7 Paginación configurable (15/25/50/100) reutilizable en `resources/js/components/shared/` si no existe ya un componente equivalente.
- [x] 5.8 Estados vacíos ("No existen usuarios registrados." / "No se encontraron usuarios con los filtros aplicados.") y skeleton de carga durante requests Inertia.
- [x] 5.9 Tras activar/desactivar/resetear contraseña: refrescar el listado conservando filtros, página y orden actuales (Inertia `router.reload` con `preserveState`/`preserveScroll` y los mismos query params); si la acción falla, mostrar el error sin cambiar visualmente el estado.

## 6. Pruebas

- [x] 6.1 `tests/Feature/Seguridad/UserControllerTest.php`: acceso permitido/denegado por `usuarios.ver`; búsqueda por nombre/email/rut; filtros por estado/rol/jurisdicción/centro financiero/centro de costo; paginación (15/25/50/100); orden inicial (activos primero, luego nombre).
- [x] 6.2 Tests de activar: solo con `usuarios.activar`; solo aplica a usuarios inactivos.
- [x] 6.3 Tests de desactivar: solo con `usuarios.desactivar`; rechaza auto-desactivación; rechaza desactivar al último `admin`/`superadmin` activo.
- [x] 6.4 Tests de reset de contraseña: solo con `usuarios.resetear_password`; la contraseña se persiste hasheada; `must_change_password` queda en `true`; la respuesta incluye la contraseña en texto plano una sola vez.
- [x] 6.5 Test de `UserPolicy` actualizado a los permisos granulares (`delete` siempre `false`).
- [x] 6.6 Test del listener de `last_login_at` en login exitoso.

## 7. Validación

- [x] 7.1 `vendor/bin/pint --dirty --format agent` sobre archivos PHP tocados.
- [x] 7.2 `composer test` (config:clear + lint:check + types:check + Pest).
- [x] 7.3 `npm run lint:check` y `npm run types:check`.
- [x] 7.4 Verificación manual en navegador: listado, búsqueda, filtros, paginación, orden, activar/desactivar, reset de contraseña, responsive (desktop/tablet/móvil), estados vacíos.
