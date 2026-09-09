## 1. Stub de propiedades reales de `Role` (Spatie)

- [x] 1.1 Creado `stubs/spatie-permission-role.stub` declarando la clase `Spatie\Permission\Models\Role` con `@property-read string|null $etiqueta` y `@property-read string|null $descripcion`, con comentario apuntando a la migración de origen.
- [x] 1.2 Registrado el stub en `phpstan.neon` bajo `parameters.stubFiles`.
- [x] 1.3 Confirmado: `vendor/bin/phpstan analyse` sobre `RoleController.php` + `GestionRolesService.php` → 0 errores (antes 10).

## 2. Tipar el closure de roles en `UserResource.php`

- [x] 2.1 En `app/Http/Resources/Seguridad/UserResource.php`, tipado el closure. Ajuste sobre el plan original: `$this->roles` resuelve a `Collection<int, Model>` genérico (la relación `HasRoles::roles()` de Spatie no declara generics — usa `Config::roleModel()` en runtime, invisible para PHPStan), así que tipar el parámetro del `map()` directamente como `Role` fallaba por contravarianza ("expects callable(Model,...), Closure(Role) given"). Se agregó `->filter(fn ($rol) => $rol instanceof Role)` antes del `map()` — Larastan reconoce ese patrón y estrecha el tipo del Collection a `Role`, dejando el `map(fn (Role $rol) => ...)` type-safe sin alterar el resultado en runtime (todo item de `$user->roles` ya es un `Role`).
- [x] 2.2 Confirmado: `vendor/bin/phpstan analyse` sobre `UserResource.php` → 0 errores (antes 4).

## 3. Forzar `Collection` base en los 3 métodos afectados por el retorno ambiguo de `Eloquent\Collection::map()`

- [x] 3.1 Ajuste sobre el plan original: `collect(...)` NO resolvía el error (se probó y confirmó empíricamente — el problema no era el retorno ambiguo de `Eloquent\Collection::map()`, sino la propia comparación invariante de PHPStan sobre `Collection<int, Shape>` como tipo de retorno declarado, que fallaba incluso con formas 100% idénticas). Fix real: cambiar el tipo de retorno de `funcionariosActivos()` de `Collection<int, array{...}>` a un `array` nativo con `@return list<array{...}>`, envolviendo el resultado en `array_values()`. Sin Collection de por medio, PHPStan usa su comparación de array-shapes nativa (madura, sin el bug de covarianza) — 0 cambio de comportamiento (Inertia serializa un `array` y una `Collection` de forma idéntica a JSON). De paso quedó más preciso: `ccosto_id` recuperó su tipo real `int<0, max>|null` (columna `unsignedBigInteger` vía `foreignId()`), que se había perdido al intentar el fix con `collect()`.
- [x] 3.2 Mismo patrón aplicado en `app/Http/Resources/Adquisiciones/ProcesoAdquisicionResource.php`: los dos closures de `whenLoaded()` (`ordenesCompraMercadoPublico`, `licitacionesMercadoPublico`) ahora retornan `array_values($collection->map(...)->all())` en vez de `$collection->map(...)->values()` (Collection). No se tocó el closure de `casosPagoProveedor`, que ya pasaba sin cambios.
- [x] 3.3 Confirmado: `vendor/bin/phpstan analyse` sobre ambos archivos → 0 errores (antes 3, "Template type TValue on class Collection is not covariant").

## 4. `array_values()` tras `array_filter()` en los comandos de importación 2182

- [x] 4.1 Agregado `array_values()` envolviendo `array_filter(array_map('trim', explode(',', ...)))` al armar `$sufijos` en `ImportarLicitaciones2182Command.php`.
- [x] 4.2 Mismo cambio aplicado en `ImportarOrdenesCompra2182Command.php`.
- [x] 4.3 Confirmado: `vendor/bin/phpstan analyse` sobre ambos archivos → 0 errores (antes 2).

## 5. Validación final

- [x] 5.1 `composer types:check` completo → 0 errores (antes 19).
- [x] 5.2 `vendor/bin/pint --dirty --format agent` → sin cambios de formato pendientes.
- [x] 5.3 `php artisan test --compact` → 903 passed / 4 skipped / 0 failed, 4124 assertions — idéntico al resultado previo al change, confirma cero cambio de comportamiento.
- [x] 5.4 `npm run types:check` y `npm run lint:check` → ambos 0 errores.
