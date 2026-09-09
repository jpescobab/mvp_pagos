## Why

`composer types:check` (PHPStan/Larastan nivel 7) falla con 19 errores desde antes del change `modulo-contratos` (confirmado con `git stash` + `phpstan analyse` contra `master`, mismos 19 errores en los mismos archivos, no relacionados con Contratos). No bloquean CI (que no corre PHPStan sobre estos archivos como gate obligatorio hoy) ni el comportamiento en producción, pero sí ensucian la señal de `composer test`/`composer ci:check` y esconden que el nivel 7 realmente está limpio en el resto del código. Con la auditoría completa del proyecto (2026-09-09) confirmando que no queda trabajo funcional en curso, es un buen momento para este housekeeping acotado antes de sumar un módulo nuevo.

## What Changes

- Se declara, vía un stub file de PHPStan, que `Spatie\Permission\Models\Role` tiene las propiedades reales `etiqueta`/`descripcion` (columnas agregadas por la migración `2026_07_28_194825_agregar_etiqueta_descripcion_a_roles.php`, que la clase vendor no declara en su propio PHPDoc) — resuelve 14 de los 19 errores en `RoleController.php`, `GestionRolesService.php` y `UserResource.php` (incluyendo los "unresolvable type" de `Collection::map()` que dependían de esa misma propiedad).
- Se tipa explícitamente el parámetro del closure `$this->roles->map(fn ($rol) => ...)` en `UserResource.php` (hoy sin type-hint, a diferencia del mismo patrón ya usado correctamente en `RoleController.php`).
- Se corrige el retorno de 3 métodos que construyen una `Collection<int, array{...}>` a partir de `Eloquent\Collection::map()` (`ProcesoAdquisicionController::funcionariosActivos()`, y dos closures en `ProcesoAdquisicionResource.php`), envolviendo el resultado con el helper `collect()` para forzar el tipo a la `Collection` base — evita el falso positivo "TValue no es covariante" que Larastan reporta cuando el retorno interno de `Eloquent\Collection::map()` es ambiguo entre `static` y la Collection base.
- Se agrega `array_values()` tras el `array_filter()` que arma `$sufijos` en `ImportarLicitaciones2182Command.php` e `ImportarOrdenesCompra2182Command.php`, para que el array realmente sea un `list<string>` reindexado (coincide con lo que `procesarCorrelativo()` ya declara y asume).

Ninguno de estos 4 cambios altera comportamiento funcional — son correcciones de tipos/anotaciones para que PHPStan describa correctamente lo que el código ya hace.

## Capabilities

### New Capabilities
- `analisis-estatico-tipos`: establece como requirement verificable que `composer types:check` (PHPStan/Larastan nivel 7) corra sin errores sobre `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/` — hasta ahora era una práctica seguida informalmente (mencionada en `CLAUDE.md`/`composer.json`) pero sin un requirement documentado; este change la deja explícita a la vez que corrige los 19 errores que la incumplían.

### Modified Capabilities
(ninguna)

## Impact

- Nuevo: `stubs/spatie-permission-role.stub` (o ubicación equivalente) + registro en `phpstan.neon` (`parameters.stubFiles`).
- Modificados: `app/Http/Controllers/Seguridad/RoleController.php`, `app/Services/Seguridad/GestionRolesService.php`, `app/Http/Resources/Seguridad/UserResource.php`, `app/Http/Controllers/Adquisiciones/ProcesoAdquisicionController.php`, `app/Http/Resources/Adquisiciones/ProcesoAdquisicionResource.php`, `app/Console/Commands/Adquisiciones/ImportarLicitaciones2182Command.php`, `app/Console/Commands/Adquisiciones/ImportarOrdenesCompra2182Command.php`.
- Sin cambios de base de datos, rutas, ni contrato HTTP/React. Sin tests nuevos (no hay comportamiento nuevo que cubrir) — la suite existente debe seguir en verde sin modificaciones.
