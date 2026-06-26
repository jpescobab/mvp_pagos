## Context

`WorkflowTransitionService::execute()` (tarea 5) bloquea transiciones comparando `workflow_transitions.documentos_requeridos` (json de strings) contra `processes.documentos_adjuntos` (json de strings) — ambos sin respaldo real: no hay archivo, no hay quién lo subió, no hay validación. El harness exige que el expediente sea variable (reglas por módulo/modalidad/monto/estado, resueltas por backend) y que una transición se bloquee si el documento "no está cargado o validado". Tampoco existe todavía un módulo funcional (Pago de Proveedores, Adquisiciones) que use esto en producción, así que se puede diseñar el modelo correcto ahora sin migrar datos reales.

No existe (y no se crea aquí) una tabla `system_modules`: el harness aún no la define y forzarla ahora sería inventar alcance. Las reglas se escopan a `workflow_definitions` (ya existente desde tarea 5), porque cada módulo funcional futuro tendrá su propio workflow — es el ancla real más cercana a "módulo" que ya existe en el sistema.

## Goals / Non-Goals

**Goals:**
- Catálogo real de tipos documentales (`document_types`), sembrado con datos reales de un proyecto hermano (FACTURA, ORDEN_COMPRA, etc.).
- Modelo de documento versionado con trazabilidad de validación (`documents`, `document_versions`, `document_links`, `document_validations`).
- Matriz de requisitos configurable por `workflow_definition` + modalidad (opcional) + rango de monto + `workflow_state` (opcional) (`document_requirement_sets`, `document_requirements`).
- Resolución de checklist por proceso, consumible por React sin lógica de negocio en el frontend (`process_document_checklists`, `process_document_checklist_items`).
- Reemplazar la validación plana de `WorkflowTransitionService` por una resolución real contra el modelo documental.

**Non-Goals:**
- No se construye el módulo Adquisiciones ni se siembran modalidades de adquisición reales (tabla `procurement_modalities` queda vacía).
- No se construye `system_modules`; el escopo de reglas usa `workflow_definitions`.
- No se implementa upload de archivos a un disco/storage específico en esta tarea (S3 vs local es decisión de la capa de infraestructura, no de este modelo de datos); `document_versions.file_path` queda como string genérico, agnóstico del disco.
- No se construye la UI React del checklist (eso es consumo del endpoint, no parte de este modelo).

## Decisions

1. **Tipo de requisito como string controlado, no enum de Postgres.** `document_requirements.tipo_requisito` acepta `requerido|opcional|condicional|recomendado` validado en el modelo/Form Request, igual que `workflow_states`/`workflow_transitions` ya usan strings de `codigo` en vez de enums de base de datos en este proyecto. Consistente con el patrón ya establecido.

2. **Reglas escopadas por `workflow_definition_id`, no por un campo `modulo` libre.** Cada módulo funcional futuro (Pago de Proveedores, Adquisiciones) tendrá su propio `WorkflowDefinition`; reutilizarlo evita inventar una tabla `system_modules` fuera de alcance.

3. **`modalidad_id` y `workflow_state_id` nullables en `document_requirements`** (`null` = aplica a cualquier modalidad / cualquier estado). Permite reglas amplias ("toda factura requiere FACTURA cargada") sin forzar una modalidad de Adquisiciones que no existe aún.

4. **`document_validations` es un log append-only de eventos de validación**, no una fila mutable. El estado "actual" de un documento es la última fila por `created_at`. Igual que `audit_logs`/`workflow_transition_logs`: nunca se edita un evento pasado, se agrega uno nuevo (ej. "rechazado" → nueva versión subida → "válido").

5. **`document_links` es polimórfico (`linkable_type`/`linkable_id`)** en vez de una FK directa a `processes`. El propio harness pide que el expediente documental no dependa de un módulo específico; mañana puede vincularse a otra entidad sin tocar el esquema. Tiene `activo` (boolean) para poder desvincular sin perder el historial.

6. **`WorkflowTransitionService::execute()` deja de leer `processes.documentos_adjuntos`** y resuelve cumplimiento así: para cada `codigo` en `workflow_transitions.documentos_requeridos`, busca un `document_link` activo del proceso cuyo documento sea de ese `document_type` y tenga una `document_validation` vigente con `estado = 'valido'`. Si falta alguno, bloquea y lista los códigos faltantes (mismo contrato de excepción que hoy, `WorkflowTransitionException::documentosFaltantes()`, sin cambiar su firma).

7. **Se elimina la columna `processes.documentos_adjuntos`** de la migración original `create_processes_table` (no se agrega una migración de borrado por separado) porque no hay datos productivos — consistente con la política de no parchear durante construcción. `Process` pierde el cast/fillable de esa columna.

8. **`processes` gana columnas genéricas `modalidad_id` (nullable, sin FK por orden de creación de tablas — `procurement_modalities` se crea en una migración posterior dentro de esta misma tarea) y `monto` (decimal nullable).** Sin esto, la resolución de `document_requirements` por modalidad/monto no tiene de dónde leer esos datos: el `subject` polimórfico todavía no existe para ningún módulo funcional (no hay `supplier_payment_case` ni similar). Poner estos dos campos en `processes` (genérico) en vez de esperar a que cada módulo los defina en su propio modelo de subject mantiene la resolución documental decoupleada de cualquier módulo funcional, igual que `documentLinks`. Se agrega a la migración original de `processes` (no una migración nueva) por la misma política de no parchear durante construcción.

9. **`process_document_checklists` es 1:1 con `process`** (un registro vigente por proceso, regenerado al abrir el expediente) en vez de versionado histórico. El historial de qué pasó con cada documento ya vive en `document_validations`/`workflow_transition_logs`; no se duplica aquí. `process_document_checklist_items` copia (`snapshot`) el `tipo_requisito` vigente al momento de resolver, para que un cambio posterior en `document_requirements` no reescriba retroactivamente un checklist ya mostrado al usuario — mismo principio que "informes razonados nacen de cortes, no de datos vivos".

## Risks / Trade-offs

- [Riesgo] Escopar reglas a `workflow_definition_id` ata la matriz documental 1:1 a la existencia de un workflow por módulo. → Mitigación: es exactamente el modelo que el harness ya impone (todo proceso de negocio futuro tiene su `WorkflowDefinition`); si en el futuro se necesita compartir reglas entre módulos, se puede agregar `document_requirement_set_id` compartido entre varios `workflow_definitions` sin romper este esquema.
- [Riesgo] Cambiar la firma interna de validación de documentos en `WorkflowTransitionService` rompe los tests actuales de `WorkflowTransitionServiceTest` que usaban `documentos_adjuntos`. → Mitigación: se actualizan esos tests para crear `document`/`document_links`/`document_validations` reales en el fixture, dentro de esta misma tarea.
- [Riesgo] `document_versions.file_path` agnóstico de disco difiere de cómo se vaya a implementar el upload real en una tarea futura. → Mitigación: es un string; cuando se implemente upload real (probablemente junto con pago-proveedores-sgf), solo cambia quién escribe ese string, no el esquema.

## Migration Plan

- Migraciones nuevas se ejecutan con `php artisan migrate` normal (no hay datos productivos, no se requiere backfill).
- `create_processes_table` se modifica en su archivo original (elimina `documentos_adjuntos`); requiere `migrate:fresh --seed` en este entorno, igual que las unificaciones anteriores de esta tarea.
