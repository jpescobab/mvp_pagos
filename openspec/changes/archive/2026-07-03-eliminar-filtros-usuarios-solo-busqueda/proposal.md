## Why

El usuario pidió simplificar el índice de Usuarios: eliminar los cinco filtros institucionales (estado, rol, jurisdicción, centro financiero, centro de costo) y dejar únicamente la búsqueda por texto. Es una decisión de producto para reducir la superficie de la pantalla, no un ajuste visual — cambia el requirement "Buscar y filtrar usuarios institucionales" de `listar-usuarios-institucionales`.

## What Changes

- `UserController::index()` deja de aceptar/aplicar `estado`, `rol_id`, `jurisdiccion_id`, `centro_financiero_id`, `centro_costo_id`; conserva `search`, `sort`, `direction`, `per_page`.
- El índice deja de recibir `catalogs` (ya no hay selects que los necesiten); `catalogos()` (usado también por `create`/`edit`) deja de incluir `jurisdicciones`, que solo se usaba en el filtro eliminado.
- Se elimina `resources/js/components/seguridad/user-filters.tsx` y se reemplaza por un input de búsqueda simple en `usuarios/index.tsx`, igual al patrón ya usado en los demás índices.
- `FiltrosUsuarios` pierde los campos de los filtros eliminados; `CatalogosUsuarios` pierde `jurisdicciones`.
- El orden ("Ordenar por") y la paginación no cambian — no son filtros, son controles de orden/paginación ya presentes en otros índices.

## Capabilities

### Modified Capabilities
- `listar-usuarios-institucionales`: el requirement "Buscar y filtrar usuarios institucionales" pasa a ser solo "Buscar usuarios institucionales" — se elimina la capacidad de filtrar por estado, rol, jurisdicción, centro financiero y centro de costo; la búsqueda por nombre/email/rut se mantiene igual.

## Impact

- Código: `app/Http/Controllers/Seguridad/UserController.php`, `resources/js/components/seguridad/user-filters.tsx` (eliminado), `resources/js/pages/seguridad/usuarios/index.tsx`, `resources/js/types/seguridad.ts`.
- Tests: `tests/Feature/Seguridad/UserControllerTest.php` — se elimina el test de filtros institucionales y se ajusta el test que verificaba `has('catalogs')`.
- Sin cambios en permisos, políticas ni en las acciones de activar/desactivar/resetear contraseña/asignar roles.
