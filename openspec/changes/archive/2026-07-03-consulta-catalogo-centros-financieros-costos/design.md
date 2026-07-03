## Context

`Cfinanciero` (`app/Models/Cfinanciero.php`) y `Ccosto` (`app/Models/Ccosto.php`) ya existen con sus migraciones y relaciones (`Cfinanciero belongsTo Jurisdiccion`, `hasMany Ccosto`; `Ccosto belongsTo Cfinanciero`), sembrados por el seeder institucional. No tienen controlador, rutas ni vistas propias — solo se consumen indirectamente como catálogos de selección (ej. `UserController::catalogos()`). El permiso `core_institucional.administrar` ya existe en `RolesAndPermissionsSeeder` y está asignado únicamente a `superadmin` y `admin`.

El patrón de referencia para listados es `ProveedorController` + `resources/js/pages/maestros/proveedores/index.tsx` (paginación, búsqueda con debounce, tabla `table-fixed`, badge de estado, menú de acciones). El patrón de autorización de referencia es `UserController`/`RoleController` con `Gate::authorize()` + Policy, registrada en `app/Providers/AppServiceProvider.php`. Ambos patrones ya incorporan la convención de tipografía reducida y botones sin relleno sólido (change `reducir-tipografia-botones-sin-relleno`, ya archivado) al ser tokens de tema — las páginas nuevas la heredan automáticamente sin trabajo adicional.

## Goals / Non-Goals

**Goals:**
- Permitir a `superadmin`/`admin` consultar el catálogo de centros financieros y centros de costo con búsqueda y paginación.
- Mostrar la relación jerárquica inmediata (Cfinanciero → su Jurisdiccion; Ccosto → su Cfinanciero) sin necesidad de navegar a otra pantalla.
- Restringir el acceso mediante Policy, consistente con el patrón ya usado para usuarios/roles.

**Non-Goals:**
- No se agregan altas, ediciones ni eliminaciones (alcance de solo lectura, igual que Proveedores hoy).
- No se modifican modelos, migraciones ni el seeder institucional.
- No se muestra la cadena completa hasta Institución (alcance limitado al nivel inmediato superior, igual que `ClienteMedidorResource` con su `ccosto`).

## Decisions

1. **Autorización vía Policy + `Gate::authorize('viewAny', ...)`, no el patrón sin autorización de `ProveedorController`.**
   `ProveedorController` actualmente no restringe el acceso (cualquier usuario autenticado puede ver proveedores). Para Centros Financieros/Costos el usuario pidió explícitamente restringir a superadmin/admin, así que se sigue el patrón de `UserPolicy`/`RolePolicy`: `CfinancieroPolicy::viewAny()` y `CcostoPolicy::viewAny()` devuelven `$user->can('core_institucional.administrar')`, registradas en `AppServiceProvider::boot()` junto a las policies existentes.
   Alternativa descartada: middleware de ruta `can:core_institucional.administrar` en vez de Policy. Se prefiere Policy porque es el patrón ya establecido en el módulo de seguridad para recursos administrativos y deja la puerta abierta a acciones futuras (`view`, `update`) sobre el mismo recurso sin cambiar de mecanismo.

2. **Un controlador y una vista por entidad, sin generalizar en un componente "listado genérico".**
   Aunque ambas vistas son casi idénticas en estructura, se replican como `CfinancieroController`/`CcostoController` y dos páginas `index.tsx` independientes, igual que `ProveedorController`/`ClienteMedidorController` hoy. Introducir una abstracción genérica de listado sería una abstracción prematura para dos casos con columnas y relaciones distintas (`design` del harness: no diseñar para necesidades hipotéticas).

3. **Paginación con `paginate(20)` + búsqueda `LIKE` por `codigo`/`nombre`, igual que `ProveedorController`.**
   Ambas tablas son pequeñas hoy (6 centros financieros, 31 centros de costo sembrados) pero se sigue el mismo patrón de paginación que Proveedores para consistencia y para no romper cuando crezcan. `Ccosto::with('cfinanciero')` y `Cfinanciero::with('jurisdiccion')` evitan N+1.

4. **Resources devuelven la relación inmediata como objeto anidado simple (`{ id, nombre }` / `{ id, codigo, nombre }`), igual que `ClienteMedidorResource`.**
   No se anida la cadena completa (jurisdicción→institución) porque no se pidió y no aporta a la tabla; si se necesita a futuro se agrega como columna adicional en un change posterior.

5. **Entradas nuevas en el sidebar dentro del grupo "Maestros" existente, visibles solo con el permiso — se agrega el share de permisos que hoy no existe.**
   Se confirmó que hoy no hay ningún mecanismo para conocer los permisos del usuario en el frontend: `HandleInertiaRequests::share()` solo comparte `auth.user` (el modelo `User` serializado, sin permisos), y `resources/js/types/auth.ts` define `Auth = { user: User }`. No hay ningún precedente de ítem de sidebar condicionado por permiso.
   Se agrega `permissions: string[]` al array `auth` compartido en `HandleInertiaRequests::share()` (vía `$request->user()?->getAllPermissions()->pluck('name') ?? []`), se extiende `Auth` en `resources/js/types/auth.ts` a `{ user: User; permissions: string[] }`, y `app-sidebar.tsx` lee `usePage<SharedData>().props.auth.permissions.includes('core_institucional.administrar')` para decidir si muestra las dos entradas nuevas del grupo "Maestros". Es un share mínimo y reutilizable por cualquier futura entrada de sidebar condicionada por permiso.

## Risks / Trade-offs

- [Riesgo] Si el frontend no tiene hoy un mecanismo para conocer los permisos del usuario autenticado (para ocultar el ítem del sidebar), habría que agregarlo → Mitigación: se revisa `HandleInertiaRequests`/`resources/js/types` antes de implementar; si falta, se agrega el share mínimo (lista de permisos o flags booleanos ya usados en otras páginas como `can_create_user`).
- [Riesgo] Repetir controlador/resource/policy por entidad duplica algo de código entre Cfinanciero y Ccosto → Mitigación: aceptado conscientemente (ver Decisión 2); el volumen es pequeño y coherente con el resto del código base.

## Migration Plan

Sin migraciones de base de datos. Se agregan archivos nuevos (controladores, resources, policies, componentes, páginas) y se editan `routes/maestros.php`, `app/Providers/AppServiceProvider.php` y `resources/js/components/app-sidebar.tsx`. Rollback trivial: revertir el commit, no hay estado persistente nuevo.
