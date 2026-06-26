## Context

Tareas 5 y 6 ya están implementadas, testeadas y archivadas con nomenclatura en inglés. No hay datos productivos (estamos en construcción), así que renombrar es seguro vía `migrate:fresh --seed`, consistente con la política de no parchear durante construcción — aquí aplicada a un rename completo en vez de a un patch de columna.

`WorkflowTransitionService::execute()` y `supplier_payment_case` están además nombrados textualmente en `CLAUDE.md` y `HARNESS_IA.md` como reglas de seguridad/arquitectura («Detenerse si... se pide saltarse `WorkflowTransitionService`»). Renombrar el código sin actualizar esos documentos dejaría al harness describiendo una clase/tabla que ya no existe con ese nombre.

## Goals / Non-Goals

**Goals:**
- Español consistente en tablas, columnas, modelos, servicio, excepción y notificación de las tareas 5 y 6.
- Harness (`CLAUDE.md`, `HARNESS_IA.md`, `openspec/config.yaml`) coherente con los nombres nuevos.
- Cero pérdida de comportamiento: mismas reglas, mismos tests (renombrados), misma cobertura.

**Non-Goals:**
- No se traducen las secciones de `HARNESS_IA.md` correspondientes a tareas todavía no construidas (integraciones/Playwright, parámetros/módulos del sistema, reportabilidad) — eso se decide cuando esas tareas se propongan, igual que se hizo con cada tarea anterior.
- No se construye `supplier_payment_case`/`caso_pago_proveedor` en este change (sigue siendo tarea 8); solo se actualiza el nombre de referencia en los documentos rectores para que sea consistente cuando se construya.
- No se cambia ningún comportamiento de negocio, regla de validación ni escenario — es un rename puro.

## Decisions

1. **Convención de traducción**: el sustantivo de dominio se traduce al español con orden gramatical natural (`IndicadorEconomico`, no `EconomicoIndicador`); los sufijos de patrón Laravel/PHP (`Service`, `Exception`, `Notification`, `Resolver`) se traducen también para mantener coherencia total en español, salvo que ya estén fijados como término de arquitectura en `CLAUDE.md` (`Services`, `Policies`, `Jobs`, etc. — esos términos describen la *carpeta*/patrón, no la clase, y no se tocan). Préstamos ya aceptados en la prosa española del propio harness (`workflow`, `snapshot`, `hash`, `checklist`) se mantienen sin traducir.
2. **`sgf_id` no se traduce**: es el identificador literal de un sistema externo (SGF), igual que los códigos institucionales (`codigo`) no se traducen — citar el campo de origen tal cual es parte de la evidencia, no nomenclatura propia.
3. **Mapeo completo de tablas**:

   | Inglés (tarea 5/6) | Español |
   |---|---|
   | `workflow_definitions` | `definiciones_workflow` |
   | `workflow_states` | `estados_workflow` |
   | `workflow_transitions` | `transiciones_workflow` |
   | `processes` | `procesos` |
   | `workflow_tasks` | `tareas_workflow` |
   | `workflow_task_assignments` | `asignaciones_tareas_workflow` |
   | `workflow_transition_logs` | `historial_transiciones_workflow` |
   | `document_types` | `tipos_documento` |
   | `documents` | `documentos` |
   | `document_versions` | `versiones_documento` |
   | `document_links` | `vinculos_documento` |
   | `document_validations` | `validaciones_documento` |
   | `procurement_modalities` | `modalidades_adquisicion` |
   | `document_requirement_sets` | `conjuntos_requisitos_documentales` |
   | `document_requirements` | `requisitos_documentales` |
   | `process_document_checklists` | `checklists_documentales_proceso` |
   | `process_document_checklist_items` | `checklist_documental_proceso_items` |

   `notifications` (tabla estándar de Laravel, generada por `make:notifications-table`) **no se renombra** — es infraestructura del framework, no nomenclatura de dominio.

4. **Mapeo completo de modelos / servicio / excepción / notificación**:

   | Inglés | Español |
   |---|---|
   | `WorkflowDefinition` | `DefinicionWorkflow` |
   | `WorkflowState` | `EstadoWorkflow` |
   | `WorkflowTransition` | `TransicionWorkflow` |
   | `Process` | `Proceso` |
   | `WorkflowTask` | `TareaWorkflow` |
   | `WorkflowTaskAssignment` | `AsignacionTareaWorkflow` |
   | `WorkflowTransitionLog` | `HistorialTransicionWorkflow` |
   | `App\Services\Workflow\WorkflowTransitionService` | `App\Services\Workflow\TransicionWorkflowService` |
   | `App\Exceptions\WorkflowTransitionException` | `App\Exceptions\TransicionWorkflowException` |
   | `App\Notifications\WorkflowTransitionNotification` | `App\Notifications\TransicionWorkflowNotification` |
   | `DocumentType` | `TipoDocumento` |
   | `Document` | `Documento` |
   | `DocumentVersion` | `VersionDocumento` |
   | `DocumentLink` | `VinculoDocumento` |
   | `DocumentValidation` | `ValidacionDocumento` |
   | `ProcurementModality` | `ModalidadAdquisicion` |
   | `DocumentRequirementSet` | `ConjuntoRequisitosDocumentales` |
   | `DocumentRequirement` | `RequisitoDocumental` |
   | `ProcessDocumentChecklist` | `ChecklistDocumentalProceso` |
   | `ProcessDocumentChecklistItem` | `ChecklistDocumentalProcesoItem` |
   | `App\Services\Documentos\DocumentValidationStatusResolver` | `App\Services\Documentos\ResolutorValidacionDocumental` |
   | `App\Services\Documentos\ProcessDocumentChecklistResolver` | `App\Services\Documentos\ResolutorChecklistDocumentalProceso` |

5. **Columnas que cambian de nombre** (las ya en español — `codigo`, `nombre`, `activo`, `comentario`, `monto`, `validado_en`, etc. — no cambian):

   `workflow_definition_id`→`definicion_workflow_id`, `from_state_id`→`estado_origen_id`, `to_state_id`→`estado_destino_id`, `current_state_id`→`estado_actual_id`, `subject_type`/`subject_id`→`sujeto_type`/`sujeto_id`, `workflow_transition_id`→`transicion_workflow_id`, `workflow_task_id`→`tarea_workflow_id`, `process_id`→`proceso_id`, `document_type_id`→`tipo_documento_id`, `uploaded_by`→`subido_por`, `document_id`→`documento_id`, `version_number`→`numero_version`, `file_path`→`ruta_archivo`, `file_name`→`nombre_archivo`, `mime_type`→`tipo_mime`, `size_bytes`→`tamano_bytes`, `linkable_type`/`linkable_id`→`vinculable_type`/`vinculable_id`, `document_requirement_set_id`→`conjunto_requisitos_documentales_id`, `workflow_state_id`→`estado_workflow_id`, `generated_at`→`generado_en`, `generated_by`→`generado_por`, `process_document_checklist_id`→`checklist_documental_proceso_id`, `document_requirement_id`→`requisito_documental_id`.

6. **Métodos de relación Eloquent se traducen junto con el modelo** (ej. `transitionLogs()`→`historialTransiciones()`, `currentState()`→`estadoActual()`, `subject()`→`sujeto()`, `linkable()`→`vinculable()`, `uploadedBy()`→`subidoPor()`) para que el código completo quede en español, no solo el esquema.

## Risks / Trade-offs

- [Riesgo] Renombrar 17 migraciones + 17 modelos + servicios + tests es mecánico pero extenso; un nombre mal escrito rompe una FK silenciosamente hasta el `migrate:fresh`. → Mitigación: el mapeo completo queda fijado arriba antes de tocar código; se corre `composer test` completo al final, no parcial.
- [Riesgo] `HARNESS_IA.md` queda con una mezcla de español (tareas 5/6/7, ya construidas o en construcción) e inglés (tareas 8/9/10, futuras) hasta que se aborden. → Mitigación: es el mismo patrón ya usado en todo el proyecto — el harness se va actualizando tarea por tarea, no de una sola vez; se deja explícito en este design.md para que la próxima sesión no lo interprete como inconsistencia accidental.

## Migration Plan

- Editar las migraciones existentes en su archivo original (incluye renombrar el archivo para que el nombre describa la tabla en español) y correr `php artisan migrate:fresh --seed`.
- No hay rollback especial: si algo falla, se corrige y se vuelve a correr `migrate:fresh --seed` (no hay datos productivos que preservar).
