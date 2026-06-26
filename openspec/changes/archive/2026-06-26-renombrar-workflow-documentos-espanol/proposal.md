## Why

Las tareas 1, 2 y 4 (core institucional, tablas maestras, indicadores económicos) usan nombres de tabla y modelo en español, siguiendo la nomenclatura real institucional CAPJ. Las tareas 5 (workflow-core) y 6 (documentos-expediente-variable) se construyeron con nombres en inglés (`processes`, `documents`, `WorkflowTransitionService`, etc.), copiando literalmente los nombres de tabla que listan `tasks/05_*.md`/`tasks/06_*.md` y `HARNESS_IA.md`. El usuario pidió alinear todo a español, incluyendo dos identificadores que el propio harness usa como referencia normativa textual (`WorkflowTransitionService`, `supplier_payment_case`), lo que exige actualizar también `CLAUDE.md`, `HARNESS_IA.md` y `openspec/config.yaml` para que sigan siendo coherentes con el código.

## What Changes

- **BREAKING**: renombrar todas las tablas, columnas, modelos, el servicio, la excepción y la notificación de `workflow-core` (tarea 5) a español: `processes`→`procesos`, `WorkflowTransitionService`→`TransicionWorkflowService`, etc. (mapeo completo en `design.md`).
- **BREAKING**: renombrar todas las tablas, columnas y modelos de `documentos-expediente-variable` (tarea 6) a español: `documents`→`documentos`, `document_types`→`tipos_documento`, etc.
- Actualizar `tests/Feature/Workflow/` y `tests/Feature/Documentos/` para usar los nombres nuevos.
- Actualizar `openspec/specs/workflow-core/spec.md`, `openspec/specs/documentos-expediente-variable/spec.md` y `openspec/specs/seguridad-auditoria/spec.md` (menciona `WorkflowTransitionService::execute()` en un escenario) para reflejar los nombres en español.
- Actualizar `CLAUDE.md` (líneas que nombran `WorkflowTransitionService`, `supplier_payment_case`) y `openspec/config.yaml` (mismo contenido, en el bloque `context:`).
- Actualizar `HARNESS_IA.md` secciones 6 (lista de tablas core), 9 y 10 (SGF/Pago de Proveedores) y 11 (Workflow Core) para usar los nombres en español ya decididos para lo construido (tareas 5/6) y el nombre de referencia `caso_pago_proveedor` para la futura tarea 8. Las secciones de tareas aún no construidas (9: integraciones/Playwright; 10: parámetros/módulos del sistema; 14: reportabilidad) **no** se traducen en este change — se traducirán cuando esas tareas se propongan, igual que se hizo con cada tarea anterior.
- Re-ejecutar `migrate:fresh --seed` (sin datos productivos) y re-validar toda la suite.

## Capabilities

### Modified Capabilities
- `workflow-core`: mismos requisitos y escenarios, solo cambian los nombres de entidades referenciadas (`procesos` en vez de `processes`, `TransicionWorkflowService` en vez de `WorkflowTransitionService`).
- `documentos-expediente-variable`: mismos requisitos y escenarios, solo cambian los nombres de entidades referenciadas.
- `seguridad-auditoria`: el escenario que menciona `WorkflowTransitionService::execute()` se actualiza al nuevo nombre.

## Impact

- ~17 migraciones existentes (tareas 5 y 6) se editan en su archivo original (no se agregan migraciones de borrado/parche) y se renombran para reflejar la tabla que crean.
- 17 modelos Eloquent, 1 servicio, 1 excepción, 1 notificación, 2 services de `App\Services\Documentos`, todos sus tests, se renombran y/o editan.
- `CLAUDE.md`, `HARNESS_IA.md`, `openspec/config.yaml` se actualizan para mantener coherencia entre harness y código.
- Requiere `php artisan migrate:fresh --seed` (sin pérdida real de datos: no hay datos productivos).
