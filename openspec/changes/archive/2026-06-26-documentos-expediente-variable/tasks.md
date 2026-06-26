## 1. Migraciones

- [x] 1.1 Crear migración `document_types` (codigo unique, nombre, descripcion nullable, es_obligatorio boolean default false, activo boolean default true)
- [x] 1.2 Crear migración `procurement_modalities` (codigo unique, nombre, activo boolean default true) — sin seed
- [x] 1.3 Crear migración `documents` (document_type_id FK, titulo nullable, uploaded_by user_id FK nullable)
- [x] 1.4 Crear migración `document_versions` (document_id FK, version_number, file_path, file_name, mime_type nullable, size_bytes nullable, hash nullable, uploaded_by FK nullable, created_at only)
- [x] 1.5 Crear migración `document_links` (document_id FK, linkable_type/linkable_id polimórfico, activo boolean default true)
- [x] 1.6 Crear migración `document_validations` (document_id FK, estado default 'pendiente', observacion nullable, validado_por FK nullable, validado_en nullable, created_at only)
- [x] 1.7 Crear migración `document_requirement_sets` (codigo unique, nombre, descripcion nullable, activo boolean default true)
- [x] 1.8 Crear migración `document_requirements` (document_requirement_set_id FK, document_type_id FK, workflow_definition_id FK, modalidad_id FK nullable a procurement_modalities, workflow_state_id FK nullable, monto_desde/monto_hasta decimal nullable, tipo_requisito string, activo boolean default true)
- [x] 1.9 Crear migración `process_document_checklists` (process_id FK unique, document_requirement_set_id FK, generated_at, generated_by FK nullable)
- [x] 1.10 Crear migración `process_document_checklist_items` (process_document_checklist_id FK, document_requirement_id FK, document_type_id FK, tipo_requisito string (snapshot), document_id FK nullable, estado_cumplimiento string default 'pendiente')
- [x] 1.11 Modificar la migración original `create_processes_table`: eliminar columna `documentos_adjuntos`; agregar `modalidad_id` (nullable, sin FK por orden de creación) y `monto` (decimal nullable) — necesarios para que `document_requirements` pueda resolver por modalidad/monto

## 2. Modelos Eloquent

- [x] 2.1 Crear `DocumentType`, `ProcurementModality`
- [x] 2.2 Crear `Document`, `DocumentVersion`, `DocumentLink` (morphTo `linkable`), `DocumentValidation`
- [x] 2.3 Crear `DocumentRequirementSet`, `DocumentRequirement`
- [x] 2.4 Crear `ProcessDocumentChecklist`, `ProcessDocumentChecklistItem`
- [x] 2.5 Actualizar `Process`: quitar `documentos_adjuntos` de `$fillable`/casts; agregar `modalidad_id`/`monto` a fillable/casts y relación `modalidad()`; agregar relación `documentLinks()` (vía `DocumentLink` polimórfico) y `checklist()` (hasOne `ProcessDocumentChecklist`)

## 3. Servicios

- [x] 3.1 Crear `App\Services\Documentos\DocumentValidationStatusResolver` (o método equivalente): dado un `Process` y un `document_type` codigo, determina si existe un documento vinculado activo con validación vigente `estado = 'valido'`
- [x] 3.2 Modificar `WorkflowTransitionService::execute()`: reemplazar la comparación `documentos_requeridos` vs `documentos_adjuntos` por la resolución real vía el resolver de 3.1; mantener `WorkflowTransitionException::documentosFaltantes()` con la misma firma
- [x] 3.3 Crear `App\Services\Documentos\ProcessDocumentChecklistResolver`: dado un `Process`, resuelve los `document_requirements` aplicables (workflow_definition_id, modalidad opcional, rango de monto, estado opcional) y genera/actualiza `process_document_checklists` + `process_document_checklist_items` (snapshot de `tipo_requisito`)

## 4. Datos semilla

- [x] 4.1 Crear `DocumentTypesSeeder` con los 10 tipos reales (FACTURA, ORDEN_COMPRA, CONTRATO, ACTA_RECEP, CERT_VIGENCIA, RESOLUCION, COMPROBANTE, NOTA_CREDITO, NOTA_DEBITO, OTRO) usando `firstOrCreate`
- [x] 4.2 Registrar el seeder en `DatabaseSeeder` en el orden correcto
- [x] 4.3 `php artisan migrate:fresh --seed` y verificar conteos

## 5. Tests

- [x] 5.1 Test feature: catálogo `document_types` se siembra con los 10 códigos reales
- [x] 5.2 Test feature: subir nueva versión de un documento no elimina versiones anteriores
- [x] 5.3 Test feature: validar un documento crea un evento en `document_validations` y el estado vigente es el más reciente
- [x] 5.4 Test feature: `ProcessDocumentChecklistResolver` genera items con el `tipo_requisito` correcto según workflow/modalidad/monto/estado
- [x] 5.5 Test feature: cambiar un `document_requirement` después de generado un checklist no altera los items ya generados
- [x] 5.6 Actualizar `WorkflowTransitionServiceTest`: reemplazar fixtures de `documentos_adjuntos` por documentos reales vinculados y validados; cubrir transición válida y bloqueo por documento faltante/no validado

## 6. Validación

- [x] 6.1 `composer lint:check`
- [x] 6.2 `composer types:check`
- [x] 6.3 `php artisan test --compact`
- [x] 6.4 `npm run lint:check` y `npm run types:check` (sin cambios de frontend esperados, pero confirmar que nada se rompe)
