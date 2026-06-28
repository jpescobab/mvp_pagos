## MODIFIED Requirements

### Requirement: Resolver checklist documental por proceso según reglas configurables
El sistema SHALL determinar los documentos requeridos de un proceso según reglas configurables por workflow, modalidad (opcional), rango de monto y estado (opcional) en `requisitos_documentales`, sin que el frontend las hardcodee.

#### Scenario: Generar checklist documental
- **WHEN** el usuario abre el expediente de un proceso
- **THEN** el backend resuelve los `requisitos_documentales` aplicables a ese proceso según su workflow, modalidad, monto y estado actual
- **AND** genera o actualiza `checklists_documentales_proceso` y sus `checklist_documental_proceso_items`
- **AND** cada item indica si es requerido, opcional, condicional o recomendado
- **AND** React solo renderiza la respuesta recibida, sin lógica de negocio propia

#### Scenario: Cambio posterior en una regla no altera un checklist ya generado
- **WHEN** un `requisito_documental` cambia después de que un proceso ya generó su checklist
- **THEN** los `checklist_documental_proceso_items` ya generados conservan el `tipo_requisito` con el que se generaron
- **AND** solo una nueva resolución del checklist refleja la regla actualizada

#### Scenario: Un item del checklist se vincula al documento real ya subido
- **WHEN** se resuelve el checklist de un proceso que tiene un `VinculoDocumento` activo cuyo documento coincide en `tipo_documento_id` con un item del checklist
- **THEN** ese item queda asociado al `documento_id` correspondiente
- **AND** su `estado_cumplimiento` refleja el estado vigente de ese documento: `cargado` si aún no tiene ningún evento de validación, o el resultado de su última validación (`valido`/`rechazado`)

#### Scenario: Varios documentos del mismo tipo vinculados al proceso
- **WHEN** existen varios `VinculoDocumento` activos cuyo documento comparte el mismo `tipo_documento_id` exigido por un item
- **THEN** el item queda asociado al documento vinculado más recientemente
