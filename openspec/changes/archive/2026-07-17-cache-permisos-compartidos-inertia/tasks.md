## 1. PermisosCompartidosResolver

- [x] 1.1 Crear `app/Services/Seguridad/PermisosCompartidosResolver.php` con método `paraUsuario(?User $user): Collection` — retorna `collect()` vacía si `$user === null`; en otro caso, envuelve en `Cache::remember()` (TTL 5 min, clave `seguridad:permisos_compartidos:{$user->id}`) un closure que preserva ambas ramas actuales (superadmin: `Permission::query()->orderBy('name')->pluck('name')`; resto: `$user->getAllPermissions()->pluck('name')`) y retorna `->values()->all()` (array plano, no la `Collection`, para evitar corrupción de serialización bajo `CACHE_STORE=database`). `paraUsuario()` reenvuelve el array leído de caché con `collect()` antes de retornar.
- [x] 1.2 Agregar `invalidarParaUsuario(int $usuarioId): void` (`Cache::forget` de la clave de ese usuario).
- [x] 1.3 Agregar `invalidarParaRol(Role $rol): void` que itera `$rol->users` y llama `invalidarParaUsuario()` por cada uno.

## 2. Integrar en HandleInertiaRequests

- [x] 2.1 `HandleInertiaRequests::share()` reemplaza la llamada a `permisosCompartidos($request->user())` por `app(PermisosCompartidosResolver::class)->paraUsuario($request->user())` (o inyección por constructor si el middleware ya sigue ese patrón para otras dependencias).
- [x] 2.2 Eliminar el método privado `permisosCompartidos()` y los imports que queden sin uso (`Permission`, `Collection` si ya no se usan directamente).

## 3. Invalidación en los puntos de escritura

- [x] 3.1 `GestionUsuariosService::asignarRoles()`: tras `$usuario->syncRoles($roles);`, llamar `$this->permisosCompartidos->invalidarParaUsuario($usuario->id);`. Agregar `PermisosCompartidosResolver $permisosCompartidos` al constructor (mismo patrón que la dependencia existente `AuditLogger`).
- [x] 3.2 `GestionRolesService::editar()`: tras `$rol->syncPermissions($datos['permissions']);`, llamar `$this->permisosCompartidos->invalidarParaRol($rol);`. Agregar `PermisosCompartidosResolver $permisosCompartidos` al constructor (mismo patrón que la dependencia existente `AuditLogger`).
- [x] 3.3 No modificar `GestionUsuariosService::crear()` ni `GestionRolesService::crear()`/`eliminar()` — documentar en comentario breve solo si no es evidente por qué no requieren invalidación (usuario/rol nuevo sin caché previa posible; `eliminar()` ya exige 0 usuarios asignados antes de borrar).

## 4. Tests

- [x] 4.1 Crear `tests/Feature/Seguridad/PermisosCompartidosResolverTest.php`: segunda llamada a `paraUsuario()` para el mismo usuario reduce a 1 sola query bajo `CACHE_STORE=database` (corrección respecto a la redacción original de esta tarea: a diferencia de `IndicadorEconomicoSelector`, este resolver no tiene memo de instancia — `Cache::remember()` siempre hace su propio `Cache::get()` de verificación, así que un hit de caché cuesta 1 query, no 0; verificado leyendo `Illuminate\Cache\Repository::rememberWithWarmth()`, que llama `$this->get($key)` antes de decidir si ejecuta el closure); test forzando `config(['cache.default' => 'database'])` que confirme que el valor leído de vuelta con `Cache::get()` directo es un array plano idéntico al calculado (prueba el round-trip de serialización real); `invalidarParaUsuario()` refleja el cambio sin esperar TTL; `invalidarParaRol()` con 2 usuarios del mismo rol, ambos reflejan el cambio sin esperar TTL.
- [x] 4.2 Agregar caso nuevo en el test feature existente de reasignación de roles de usuario: tras reasignar roles, una carga de página posterior del usuario afectado refleja los permisos nuevos sin esperar TTL.
- [x] 4.3 Agregar caso nuevo en el test feature existente de gestión de roles: tras editar los permisos de un rol con usuarios asignados, esos usuarios reflejan el cambio sin esperar TTL.
- [x] 4.4 Confirmar que `tests/Feature/Seguridad/PermisosCompartidosInertiaTest.php` sigue pasando sin modificación (valida el contrato de las dos ramas que este change no debe alterar).

## 5. Validación

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 5.2 `php artisan test --compact --filter=Seguridad`.
- [x] 5.3 `composer test` completo antes de cerrar el change.
