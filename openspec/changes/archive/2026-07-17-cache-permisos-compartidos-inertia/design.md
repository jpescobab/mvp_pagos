## Context

`HandleInertiaRequests::permisosCompartidos(?User $user)` corre en `share()`, es decir en cada request Inertia autenticado de toda la app. Datos verificados contra la base de datos real: 38 permisos totales; el rol `admin` tiene 37 asignados directamente vía `role_has_permissions`, `superadmin` 16, `jefe_finanzas` 8, `administrativo_finanzas` 10. Para un usuario con rol `admin`, `$user->getAllPermissions()->pluck('name')` hidrata hasta 37 instancias Eloquent de `Permission` en cada página — el caché interno de Spatie (`config/permission.php`, TTL 24h) cachea el grafo rol→permiso completo, pero `getAllPermissions()` igual reconstruye y pluckea la lista final en cada llamada. Para `superadmin`, `Permission::query()->orderBy('name')->pluck('name')` evita hidratar modelos (pluck) pero es una consulta SQL sin caché propia en cada página.

Verificado en `config/cache.php` (línea 134, `serializable_classes => false`) y en `DatabaseStore::unserialize()` (`vendor/laravel/framework/src/Illuminate/Cache/DatabaseStore.php`, líneas 593-595): bajo `CACHE_STORE=database`, `unserialize($value, ['allowed_classes' => false])` no reconstruye objetos PHP al leer de caché — cachear una `Collection` (o cualquier objeto) devuelve un `__PHP_Incomplete_Class` al leerlo de vuelta, corrompiendo el dato silenciosamente. `IndicadorEconomicoSelector` (change `optimizar-cache-database-indicadores`) ya evita esto cacheando arrays planos; este servicio nuevo debe seguir la misma disciplina.

Precedente directo de patrón: `app/Services/PagoProveedores/CfinancieroPorDefectoResolver.php` — `Cache::remember()` de una sola clave con TTL, para un dato que cambia con poca frecuencia. A diferencia de `IndicadorEconomicoSelector` (que resuelve N códigos en una llamada), aquí solo hay un caller (`HandleInertiaRequests::share()`) y una clave por usuario, así que no hace falta memoización de instancia ni `Cache::many()`.

Sitios de escritura que invalidan la caché: `GestionUsuariosService::asignarRoles()` (llama `syncRoles()`) y `GestionRolesService::editar()` (llama `syncPermissions()`). `GestionUsuariosService::crear()` y `GestionRolesService::crear()` no requieren invalidación (usuario/rol nuevo, sin caché previa posible). `GestionRolesService::eliminar()` ya exige `$rol->users()->exists() === false` antes de borrar, así que tampoco hay usuarios que invalidar en ese punto.

## Goals / Non-Goals

**Goals:**
- Evitar recalcular (y rehidratar modelos `Permission`) la lista de permisos compartidos en cada request dentro de un TTL corto.
- Mantener ambas ramas de comportamiento (superadmin vs. resto) exactamente como están hoy.
- Invalidar la caché en los puntos de escritura reales (reasignar roles de un usuario, editar permisos de un rol), no depender solo del TTL.

**Non-Goals:**
- No cambia la autorización real — `Gate`/`Policy` siguen re-evaluando en cada acción, independiente de esta lista compartida al frontend (que solo condiciona UI).
- No cachea el catálogo completo de permisos (ya lo cachea Spatie internamente, TTL 24h).
- No crea un mecanismo de invalidación para "permiso nuevo agregado al catálogo" — eso ocurre vía seeder en deploy, no en runtime; no hay UI para crear permisos nuevos en caliente.
- No incluye el change de indicadores económicos (`optimizar-cache-database-indicadores`) — es un change separado.

## Decisions

**1. `Cache::remember()` por usuario, retornando un array plano, no la `Collection`.**
El closure cacheado retorna `->values()->all()` (array indexado de strings), y `paraUsuario()` lo re-envuelve con `collect()` al leer. Cachear la `Collection` directamente se corrompería silenciosamente bajo `CACHE_STORE=database` (ver Context). Ambas ramas (superadmin / resto) se preservan intactas dentro del mismo closure.

**2. TTL de 5 minutos, igual al de `IndicadorEconomicoSelector`.**
Alternativa considerada: TTL más largo (15-60 min) ya que esto es solo para condicionar UI, no autorización real. Descartada por consistencia: no hay razón clara para que el mismo tipo de problema (prop compartida en cada request Inertia) tenga un TTL distinto en dos servicios del mismo codebase: un TTL diferente sin justificación clara es más carga cognitiva para quien mantenga esto después que beneficio real, dado que la invalidación explícita en los puntos de escritura ya cubre el caso que más importa (un admin cambia roles y espera ver el efecto pronto).

**3. Invalidación por usuario y por rol, sin batching.**
`invalidarParaRol()` itera `$rol->users` y llama `invalidarParaUsuario()` por cada uno. Es una acción administrativa de baja frecuencia (editar permisos de un rol), no un hot path — un loop simple de `Cache::forget()` es suficiente y más legible que introducir `Cache::deleteMultiple()` u otra abstracción para un caso de uso infrecuente. Si en el futuro un rol llega a tener cientos de usuarios, este punto debería revisarse — no hay datos hoy que lo justifiquen (0 usuarios con rol asignado en la base local).

## Risks / Trade-offs

- **Cachear la `Collection` en vez de un array plano** → corrompe el dato leído de vuelta bajo `CACHE_STORE=database`, invisible en tests porque el store `array` de test no serializa nada. Mitigación: test dedicado que fuerza `config(['cache.default' => 'database'])` y verifica el round-trip real.
- **Invalidación olvidada en algún punto de escritura de roles/permisos** → dejaría UI desactualizada hasta 5 min, no un problema de seguridad (la autorización real no depende de esta caché), pero sí de confusión para el usuario. Mitigación: tests nuevos en `GestionUsuariosService`/`GestionRolesService` que confirman el efecto inmediato tras la escritura.
- **Costo del loop en `invalidarParaRol()` no acotado por datos reales** (0 usuarios por rol en la BD local hoy) — aceptable dado que es una acción administrativa rara; revisar si algún rol institucional llega a tener un volumen alto de usuarios asignados.
