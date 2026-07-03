## Why

Los modelos `Cfinanciero` (centros financieros) y `Ccosto` (centros de costos) ya existen en la base de datos como parte de la jerarquía institucional fija `instituciones -> jurisdicciones -> cfinancieros -> ccostos`, y ya se usan como catálogos de selección en otros módulos (ej. asignación de funcionarios, filtros de usuarios). Sin embargo, no existe ninguna vista donde superadmin o admin puedan consultar estos dos niveles de la jerarquía directamente — hoy solo son visibles indirectamente a través de dropdowns en otras pantallas. Faltan las vistas de consulta (index) que permitan auditar y navegar esta estructura maestra.

## What Changes

- Agregar controlador `CfinancieroController@index` y vista `maestros/cfinancieros/index` con listado paginado, búsqueda por código/nombre y columna de jurisdicción asociada.
- Agregar controlador `CcostoController@index` y vista `maestros/ccostos/index` con listado paginado, búsqueda por código/nombre y columna de centro financiero asociado.
- Ambas vistas siguen la convención de "Listados tabulares densos" ya formalizada (`openspec/specs/tema-visual-layout/spec.md`): tabla `table-fixed`, badge de estado activo/inactivo, columnas secundarias truncadas con tooltip, ocultamiento progresivo en viewports angostos, menú de acciones desplegable con acciones no implementadas deshabilitadas ("Disponible próximamente").
- Autorización nueva vía `CfinancieroPolicy` y `CcostoPolicy`, ambas con `viewAny` restringido al permiso existente `core_institucional.administrar` (ya asignado solo a `superadmin` y `admin`), siguiendo el patrón `Gate::authorize()` usado en `UserController`/`RoleController`.
- Nuevas rutas `maestros/cfinancieros` y `maestros/ccostos` (GET, `auth` + policy) agregadas a `routes/maestros.php`.
- Nuevas entradas en el grupo "Maestros" del sidebar (`resources/js/components/app-sidebar.tsx`), visibles solo si el usuario tiene el permiso `core_institucional.administrar`.
- Alcance de solo lectura: no se agregan altas, ediciones ni eliminaciones de centros financieros/costos en este change.

## Capabilities

### New Capabilities
- `consulta-catalogo-centros-financieros-costos`: consulta paginada y con permisos restringidos de los catálogos de centros financieros y centros de costo de la jerarquía institucional CAPJ.

### Modified Capabilities
(ninguna — no se modifican requisitos de `core-institucional-capj` ni `tema-visual-layout`, solo se aplican los ya existentes)

## Impact

- Código nuevo: `app/Http/Controllers/Maestros/CfinancieroController.php`, `app/Http/Controllers/Maestros/CcostoController.php`, `app/Http/Resources/Maestros/CfinancieroResource.php`, `app/Http/Resources/Maestros/CcostoResource.php`, `app/Policies/CfinancieroPolicy.php`, `app/Policies/CcostoPolicy.php`, componentes React de badge/menú de acciones y páginas `index.tsx` para ambas entidades.
- Código modificado: `routes/maestros.php` (nuevas rutas), `resources/js/components/app-sidebar.tsx` (nuevas entradas), `resources/js/types/maestros.ts` (nuevos tipos), `AuthServiceProvider`/registro de policies si aplica.
- Sin cambios en modelos ni migraciones (`Cfinanciero`, `Ccosto` ya están completos).
- Sin impacto en workflow, SGF, snapshots ni auditoría — son catálogos de solo lectura.
