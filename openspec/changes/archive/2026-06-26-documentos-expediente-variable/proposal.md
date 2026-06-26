## Why

Workflow-core (tarea 5) bloquea transiciones comparando un json plano de strings (`workflow_transitions.documentos_requeridos` vs `processes.documentos_adjuntos`), un stand-in temporal porque el módulo documental todavía no existía. El harness exige que el expediente documental sea variable (los requisitos dependen de módulo/proceso/modalidad/monto/estado, resueltos por el backend) y que una transición se bloquee si el documento "no está cargado o validado" — un estado real, no un string suelto. Se necesita el catálogo y modelo documental real para que esa validación deje de ser un placeholder.

## What Changes

- Crear catálogo `document_types` (sembrado con datos reales: FACTURA, ORDEN_COMPRA, CONTRATO, ACTA_RECEP, CERT_VIGENCIA, RESOLUCION, COMPROBANTE, NOTA_CREDITO, NOTA_DEBITO, OTRO).
- Crear modelo documental real: `documents`, `document_versions`, `document_links` (vínculo polimórfico documento↔entidad, ej. `Process`), `document_validations` (estado de validación: pendiente/válido/rechazado).
- Crear matriz de requisitos configurable: `procurement_modalities` (catálogo vacío, sin seed — pertenece al futuro módulo Adquisiciones), `document_requirement_sets`, `document_requirements` (regla: módulo + modalidad opcional + rango de monto + estado + tipo documental + obligatoriedad: requerido/opcional/condicional/recomendado).
- Crear resolución de checklist por proceso: `process_document_checklists`, `process_document_checklist_items` — generados por el backend al abrir el expediente; React solo renderiza la respuesta.
- **BREAKING**: eliminar la columna `processes.documentos_adjuntos` (json plano) de su migración original (no hay datos productivos) y mover la validación de documentos requeridos en `WorkflowTransitionService::execute()` desde esa comparación plana a una resolución contra `document_links` + `document_validations` (documento cargado y validado) para los `document_types.codigo` listados en `workflow_transitions.documentos_requeridos`.

## Capabilities

### New Capabilities
- `documentos-expediente-variable`: catálogo de tipos documentales, modelo de documentos versionado con validación, matriz de requisitos configurable por módulo/modalidad/monto/estado, y resolución de checklist por proceso consumible por React sin lógica hardcodeada.

### Modified Capabilities
- `workflow-core`: `WorkflowTransitionService::execute()` deja de comparar `documentos_adjuntos` (json plano) y bloquea la transición resolviendo el cumplimiento real (documento cargado y validado) contra el modelo documental de `documentos-expediente-variable`.

## Impact

- Migraciones nuevas: `document_types`, `documents`, `document_versions`, `document_links`, `document_validations`, `procurement_modalities`, `document_requirement_sets`, `document_requirements`, `process_document_checklists`, `process_document_checklist_items`.
- Migración modificada (unificada, no parche): `create_processes_table` pierde la columna `documentos_adjuntos`.
- Código modificado: `App\Services\Workflow\WorkflowTransitionService`, modelo `App\Models\Process`.
- Tests existentes afectados: `tests/Feature/Workflow/WorkflowTransitionServiceTest.php` (los casos de documento faltante deben pasar a usar el modelo documental real).
- Nuevo seeder: `DocumentTypesSeeder` con los 10 tipos reales.
