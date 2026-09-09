## Context

`composer types:check` corre `phpstan analyse` a nivel 7 (Larastan) sobre `app/`, `bootstrap/app.php`, `config/`, `database/`, `routes/`. 19 errores preexistentes (confirmados no relacionados con ningún change reciente vía `git stash` contra `master`) se agrupan en 3 causas raíz reales, ya diagnosticadas leyendo el código:

1. **Propiedad real no declarada en clase vendor** (14 errores): `roles.etiqueta`/`roles.descripcion` son columnas reales (migración `2026_07_28_194825_agregar_etiqueta_descripcion_a_roles.php`) que la app lee/escribe en `Spatie\Permission\Models\Role` directamente (sin subclase propia), pero la clase vendor no las declara en su PHPDoc — PHPStan no puede saber que existen.
2. **Retorno ambiguo de `Eloquent\Collection::map()`** (3 errores): `Eloquent\Collection::map()` devuelve `static|Illuminate\Support\Collection` según si el resultado sigue siendo de modelos — esa ambigüedad no calza de forma invariante con un `@return Collection<int, array{...}>` declarado explícito, aunque las formas impresas luzcan idénticas (documentado en el blog de PHPStan sobre `@template-covariant`: los templates son invariantes por defecto).
3. **`array_filter()` no garantiza reindexado** (2 errores): un método privado declara `@param list<string>` pero el caller arma el array con `array_filter(array_map(...))`, que puede dejar huecos en las claves — PHPStan ve `array<int, non-falsy-string>`, no un `list` garantizado.

## Goals / Non-Goals

**Goals:**
- `composer types:check` en 0 errores.
- Cada fix corrige la causa real (tipos que hoy mienten sobre lo que el código hace), no la esconde.
- Cero cambio de comportamiento funcional — la suite de tests existente pasa sin modificarse.

**Non-Goals:**
- No se crea un modelo `App\Models\Role` propio ni se cambia `config('permission.models.role')` — el stub file resuelve el problema sin ese cambio, más invasivo y no pedido.
- No se audita ni se corrige ningún otro nivel de PHPStan más allá de estos 19 errores puntuales.
- No se sube el `level` de PHPStan ni se agregan reglas nuevas — alcance estrictamente estos 19 errores ya identificados.

## Decisions

### 1. Stub file para `Spatie\Permission\Models\Role`, no una subclase propia
Se declara `@property-read string|null $etiqueta` y `@property-read string|null $descripcion` sobre la clase vendor vía un stub file de PHPStan (`parameters.stubFiles` en `phpstan.neon`) — mecanismo oficial y documentado de PHPStan para "una clase de terceros tiene una propiedad real (columna de BD) que su propio código no declara". No es un `@phpstan-ignore` ni una supresión: describe con precisión lo que la clase realmente tiene en runtime.

Alternativa descartada: crear `App\Models\Role extends Spatie\Permission\Models\Role` y apuntar `config('permission.models.role')` ahí, migrando los imports de `RoleController`/`GestionRolesService`. Se descarta por ahora porque es un cambio más grande e invasivo (toca configuración de un paquete de terceros, y el mismo problema en `UserResource.php` seguiría sin resolverse solo con eso, porque la relación `HasRoles::roles()` de Spatie tiene su propio `@return MorphToMany<Role, ...>` fijado a la clase vendor en su PHPDoc — el stub resuelve ambos casos con un solo cambio).

### 2. Devolver `array` nativo (`list<Shape>`) en vez de `Collection<int, Shape>`
**Actualizado tras implementar (la hipótesis inicial de este documento no se sostuvo empíricamente):** se probó primero envolver `->map(...)->values()->all()` con el helper global `collect(...)` (forzando `Illuminate\Support\Collection` explícita, descartando la ambigüedad de `Eloquent\Collection::map()`). No resolvió el error — el mismo "TValue no es covariante" persistió con formas impresas 100% idénticas a ambos lados, confirmando que la causa no era la ambigüedad del retorno de `Eloquent\Collection::map()`, sino la propia comparación invariante de PHPStan al verificar un `Collection<int, Shape>` como tipo de retorno declarado del método/closure — un límite real de la versión instalada de Larastan (3.10.0)/PHPStan (2.2.2) para este patrón específico, no un bug en el código de la app.

Fix real aplicado: cambiar el tipo de retorno declarado de `Collection<int, array{...}>` a un `array` nativo (`@return list<array{...}>`), envolviendo el resultado en `array_values()` para garantizar que sea una lista reindexada. Sin una `Collection` de por medio, PHPStan usa su comparación de array-shapes nativa (madura, sin el bug de covarianza de templates) y el error desaparece. Cero cambio de comportamiento: Inertia/JsonResource serializan un `array` y una `Collection` de forma idéntica a JSON, y ninguno de los 3 sitios encadena métodos de Collection sobre el resultado después de esta llamada. Como beneficio adicional, `ProcesoAdquisicionController::funcionariosActivos()` recuperó la precisión real de `ccosto_id: int<0, max>|null` (columna `unsignedBigInteger` vía `foreignId()`), que se había perdido al intentar la primera hipótesis con `collect()`.

Alternativa descartada: quitar el `@return` tipado y dejar que PHPStan infiera el tipo — se descarta porque reduce la precisión del tipo documentado para quien llame a estos métodos, sin necesidad (el fix con `array`/`list<>` mantiene la misma precisión sin ese costo).

### 3. `array_values()` después de `array_filter()`
Fix mínimo y semánticamente correcto: si el código ya asume (vía el `@param list<string>` existente) que `$sufijos` es una lista sin huecos, `array_values()` lo hace explícito y verdadero en vez de dejarlo como una suposición implícita que `array_filter()` no garantiza.

Alternativa descartada: relajar el `@param` de `procesarCorrelativo()` a `array<int, string>` — se descarta porque es debilitar el tipo para acomodar el caller en vez de corregir el caller, exactamente lo que las instrucciones de PHPStan piden evitar ("no ensanchar tipos solo para que el error desaparezca").

## Risks / Trade-offs

- [Riesgo] Un stub file que declara una propiedad incorrecta (ej. tipo equivocado) engañaría a PHPStan de forma más silenciosa que un error visible → Mitigación: el tipo declarado (`string|null` para ambas columnas) coincide exactamente con la migración (`$table->string('etiqueta')->nullable()`, `$table->string('descripcion', 500)->nullable()`), verificado antes de escribir el stub.
- [Riesgo] Si en el futuro se agrega una columna nueva a `roles` sin actualizar el stub, PHPStan no la reconocerá (mismo problema, en otra propiedad) → Mitigación: aceptado, es el mismo trade-off inherente a cualquier stub file; documentado con un comentario en el stub apuntando a la migración de origen.

## Migration Plan

Cambio de solo tipos/anotaciones, sin migración de datos ni despliegue especial. Verificar con `composer types:check` (0 errores esperados) y `php artisan test` (sin cambios esperados) antes de dar por cerrado.
