## Why

El listado de "Importaciones SGF" hoy solo comunica el estado técnico de la corrida (`en_progreso`/`completado`/`error`/`huerfano`), pero no dice en qué etapa del workflow interno quedaron los casos que produjo, así que quien opera no ve de un vistazo si esos pagos están en Finanzas, en el Administrador Zonal o más adelante. Además, las corridas fallidas (error/huérfana, con 0 elementos) se acumulan sin forma de limpiarlas, y el filtro por defecto muestra justo las que aún requieren atención en vez de las completadas, que son las que normalmente se consultan.

## What Changes

- **Desglose por etapa**: el listado agrega, por importación, un resumen de cuántos casos hay en cada etapa del workflow interno (ej. "3 En revisión Finanzas · 2 Zonal · 1 Lista para CGU"), ordenado por el orden del workflow. Las corridas sin casos muestran un vacío explícito. La agregación la calcula el backend de forma eficiente (sin N+1); React solo la renderiza.
- **Eliminar importación (acotado por trazabilidad)**: nueva acción para eliminar una corrida **únicamente** cuando no produjo casos ni snapshots (típicamente error/huérfana/0 elementos) y no está `en_progreso`. Borra el `trabajo_integracion` y sus artefactos propios del intento (ejecuciones de automatización + pasos, solicitudes API), registra la eliminación en auditoría, y **nunca** toca snapshots, casos ni auditoría preexistente. Una corrida completada con casos no es elegible: la acción se muestra deshabilitada con explicación.
- **Permiso nuevo** `integraciones_sgf.eliminar_importacion`, asignado a `superadmin` y `jefe_finanzas`. La acción de eliminar se condiciona a este permiso en backend (policy/Gate) y en UI.
- **BREAKING** (comportamiento del filtro por defecto): el listado, sin filtro de estado explícito, pasa a mostrar **solo** las corridas `completado` (antes mostraba todo menos `completado`). Se conservan las opciones "No completadas" (el comportamiento anterior), "Todos los estados" y los filtros por estado puntual. El filtro de estado sigue combinándose con la búsqueda.

## Capabilities

### New Capabilities
<!-- Ninguna capability nueva: se extiende la existente de consulta/gestión de importaciones. -->

### Modified Capabilities
- `consulta-importaciones-sgf`: (1) el requirement "Listar las corridas de importación SGF" invierte el filtro por defecto (de "excluir completado" a "solo completado") y agrega el desglose de etapas del workflow por corrida; (2) se agrega un requirement nuevo para eliminar corridas sin trazabilidad, gobernado por el permiso `integraciones_sgf.eliminar_importacion` y por la guardia de no borrar snapshots/casos/auditoría.

## Impact

- **Backend (Services; controladores livianos)**:
  - `app/Http/Controllers/Sgf/ImportacionSgfController.php` — `index` delega en un Service/Presenter que arma la página con el desglose de etapas; cambia el default del filtro.
  - Nuevo `app/Http/Controllers/Sgf/EliminarImportacionSgfController.php` (`destroy`) delgado → nuevo `app/Services/Integraciones/EliminarImportacionSgfService.php` (guardia + borrado transaccional + auditoría).
  - Nuevo Service/Presenter para el desglose de etapas (agregación por página, sin N+1, sobre `CasoPagoProveedor.proceso.estadoActual`).
  - `app/Http/Resources/Sgf/ImportacionSgfResource.php` — expone `desglose_estados` en el listado.
  - Policy/Gate `eliminarImportacionSgf` + permiso nuevo en `RolesAndPermissionsSeeder`.
- **Rutas**: `routes/sgf.php` agrega `DELETE sgf/importaciones/{trabajoIntegracion}`. Wayfinder regenerado; el frontend usa el helper tipado.
- **Frontend**: `resources/js/pages/sgf/importaciones/index.tsx` — columna de desglose, ítem "Eliminar" en el dropdown (condicionado a permiso + elegibilidad, con confirmación), y default del `<Select>` en "Completadas". `resources/js/types/sgf.ts` — tipos del desglose.
- **Sin migraciones**: borrado físico solo de corridas sin trazabilidad; no se agrega soft delete.
- **Harness**: respeta "no eliminar trazabilidad/snapshots/auditoría" (guardia dura + auditar la eliminación); controladores livianos; no toca `TransicionWorkflowService`.
- **Tests Feature**: desglose correcto (multi-etapa y sin casos); eliminación permitida solo sin snapshots + auditada; bloqueo con snapshots/casos, sin permiso y `en_progreso`; default = completadas y combinación con búsqueda.
