## 1. Migraciones de workflow-core

- [x] 1.1 Renombrar archivo y contenido: `workflow_definitions` → `definiciones_workflow`
- [x] 1.2 Renombrar archivo y contenido: `workflow_states` → `estados_workflow` (FK `workflow_definition_id`→`definicion_workflow_id`)
- [x] 1.3 Renombrar archivo y contenido: `workflow_transitions` → `transiciones_workflow` (FKs `workflow_definition_id`→`definicion_workflow_id`, `from_state_id`→`estado_origen_id`, `to_state_id`→`estado_destino_id`)
- [x] 1.4 Renombrar archivo y contenido: `processes` → `procesos` (FKs `workflow_definition_id`→`definicion_workflow_id`, `current_state_id`→`estado_actual_id`, `subject_type`/`subject_id`→`sujeto_type`/`sujeto_id`)
- [x] 1.5 Renombrar archivo y contenido: `workflow_tasks` → `tareas_workflow` (FKs `process_id`→`proceso_id`, `workflow_transition_id`→`transicion_workflow_id`)
- [x] 1.6 Renombrar archivo y contenido: `workflow_task_assignments` → `asignaciones_tareas_workflow` (FK `workflow_task_id`→`tarea_workflow_id`)
- [x] 1.7 Renombrar archivo y contenido: `workflow_transition_logs` → `historial_transiciones_workflow` (FKs `process_id`→`proceso_id`, `workflow_transition_id`→`transicion_workflow_id`, `from_state_id`→`estado_origen_id`, `to_state_id`→`estado_destino_id`)

## 2. Migraciones de documentos-expediente-variable

- [x] 2.1 Renombrar archivo y contenido: `document_types` → `tipos_documento`
- [x] 2.2 Renombrar archivo y contenido: `procurement_modalities` → `modalidades_adquisicion`
- [x] 2.3 Renombrar archivo y contenido: `documents` → `documentos` (FK `document_type_id`→`tipo_documento_id`, `uploaded_by`→`subido_por`)
- [x] 2.4 Renombrar archivo y contenido: `document_versions` → `versiones_documento` (FK `document_id`→`documento_id`, columnas `version_number`→`numero_version`, `file_path`→`ruta_archivo`, `file_name`→`nombre_archivo`, `mime_type`→`tipo_mime`, `size_bytes`→`tamano_bytes`, `uploaded_by`→`subido_por`)
- [x] 2.5 Renombrar archivo y contenido: `document_links` → `vinculos_documento` (FK `document_id`→`documento_id`, columnas `linkable_type`/`linkable_id`→`vinculable_type`/`vinculable_id`)
- [x] 2.6 Renombrar archivo y contenido: `document_validations` → `validaciones_documento` (FK `document_id`→`documento_id`)
- [x] 2.7 Renombrar archivo y contenido: `document_requirement_sets` → `conjuntos_requisitos_documentales`
- [x] 2.8 Renombrar archivo y contenido: `document_requirements` → `requisitos_documentales` (FKs `document_requirement_set_id`→`conjunto_requisitos_documentales_id`, `document_type_id`→`tipo_documento_id`, `workflow_definition_id`→`definicion_workflow_id`, `workflow_state_id`→`estado_workflow_id`)
- [x] 2.9 Renombrar archivo y contenido: `process_document_checklists` → `checklists_documentales_proceso` (FKs `process_id`→`proceso_id`, `document_requirement_set_id`→`conjunto_requisitos_documentales_id`, `generated_at`→`generado_en`, `generated_by`→`generado_por`)
- [x] 2.10 Renombrar archivo y contenido: `process_document_checklist_items` → `checklist_documental_proceso_items` (FKs `process_document_checklist_id`→`checklist_documental_proceso_id`, `document_requirement_id`→`requisito_documental_id`, `document_type_id`→`tipo_documento_id`, `document_id`→`documento_id`)

## 3. Modelos workflow-core

- [x] 3.1 `WorkflowDefinition`→`DefinicionWorkflow`, `WorkflowState`→`EstadoWorkflow`, `WorkflowTransition`→`TransicionWorkflow` (relaciones y FKs actualizadas)
- [x] 3.2 `Process`→`Proceso` (incluye `sujeto()` en vez de `subject()`, `modalidad()`, `documentLinks()`→`vinculosDocumento()`, `checklist()`)
- [x] 3.3 `WorkflowTask`→`TareaWorkflow`, `WorkflowTaskAssignment`→`AsignacionTareaWorkflow`, `WorkflowTransitionLog`→`HistorialTransicionWorkflow`

## 4. Modelos documentos-expediente-variable

- [x] 4.1 `DocumentType`→`TipoDocumento`, `ProcurementModality`→`ModalidadAdquisicion`
- [x] 4.2 `Document`→`Documento`, `DocumentVersion`→`VersionDocumento`, `DocumentLink`→`VinculoDocumento` (incluye `vinculable()` en vez de `linkable()`), `DocumentValidation`→`ValidacionDocumento`
- [x] 4.3 `DocumentRequirementSet`→`ConjuntoRequisitosDocumentales`, `DocumentRequirement`→`RequisitoDocumental`
- [x] 4.4 `ProcessDocumentChecklist`→`ChecklistDocumentalProceso`, `ProcessDocumentChecklistItem`→`ChecklistDocumentalProcesoItem`

## 5. Servicio, excepción, notificación y resolutores

- [x] 5.1 `App\Services\Workflow\WorkflowTransitionService`→`TransicionWorkflowService`
- [x] 5.2 `App\Exceptions\WorkflowTransitionException`→`TransicionWorkflowException`
- [x] 5.3 `App\Notifications\WorkflowTransitionNotification`→`TransicionWorkflowNotification`
- [x] 5.4 `App\Services\Documentos\DocumentValidationStatusResolver`→`ResolutorValidacionDocumental`
- [x] 5.5 `App\Services\Documentos\ProcessDocumentChecklistResolver`→`ResolutorChecklistDocumentalProceso`

## 6. Seeders y datos

- [x] 6.1 Actualizar `DocumentTypesSeeder` (clase y referencia a `TipoDocumento`); renombrar a `TiposDocumentoSeeder` si corresponde
- [x] 6.2 Actualizar `DatabaseSeeder` con las clases renombradas

## 7. Tests

- [x] 7.1 Renombrar y actualizar `tests/Feature/Workflow/WorkflowTransitionServiceTest.php` → `TransicionWorkflowServiceTest.php`
- [x] 7.2 Renombrar y actualizar `tests/Feature/Documentos/*.php` con los nombres nuevos

## 8. Specs y harness

- [x] 8.1 Sincronizar deltas de `workflow-core`, `documentos-expediente-variable`, `seguridad-auditoria` a `openspec/specs/`
- [x] 8.2 Actualizar `CLAUDE.md` (líneas que nombran `WorkflowTransitionService`, `supplier_payment_case`)
- [x] 8.3 Actualizar `openspec/config.yaml` (bloque `context:`, mismo contenido que CLAUDE.md)
- [x] 8.4 Actualizar `HARNESS_IA.md` secciones 6, 8, 10 y 11 con los nombres en español decididos (sin tocar secciones de tareas futuras no construidas)

## 9. Validación

- [x] 9.1 `php artisan migrate:fresh --seed`
- [x] 9.2 `composer lint:check`
- [x] 9.3 `composer types:check`
- [x] 9.4 `php artisan test --compact`
