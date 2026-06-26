## ADDED Requirements

### Requirement: Mantener catálogo de tipos documentales reutilizable
El sistema SHALL mantener un catálogo de `document_types` (código único, nombre, si es obligatorio por defecto, activo) reutilizable por cualquier matriz de requisitos de cualquier módulo funcional futuro.

#### Scenario: Tipo documental disponible para reglas
- **WHEN** se crea una regla de requisito documental en `document_requirements`
- **THEN** debe referenciar un `document_type` activo del catálogo

### Requirement: Versionar documentos con trazabilidad de validación
Todo documento cargado al expediente SHALL quedar asociado a un tipo documental, conservar todas sus versiones (`document_versions`) y registrar un historial inmutable de eventos de validación (`document_validations`). El estado vigente de un documento es el del evento de validación más reciente.

#### Scenario: Subir una nueva versión de un documento
- **WHEN** se carga un archivo para un documento existente
- **THEN** se crea una nueva fila en `document_versions`
- **AND** las versiones anteriores no se eliminan

#### Scenario: Validar un documento
- **WHEN** un usuario autorizado valida o rechaza un documento
- **THEN** se registra un nuevo evento en `document_validations` con el resultado y la observación
- **AND** el estado vigente del documento pasa a ser el de ese evento

### Requirement: Resolver checklist documental por proceso según reglas configurables
El sistema SHALL determinar los documentos requeridos de un proceso según reglas configurables por workflow, modalidad (opcional), rango de monto y estado (opcional) en `document_requirements`, sin que el frontend las hardcodee.

#### Scenario: Generar checklist documental
- **WHEN** el usuario abre el expediente de un proceso
- **THEN** el backend resuelve los `document_requirements` aplicables a ese proceso según su workflow, modalidad, monto y estado actual
- **AND** genera o actualiza `process_document_checklists` y sus `process_document_checklist_items`
- **AND** cada item indica si es requerido, opcional, condicional o recomendado
- **AND** React solo renderiza la respuesta recibida, sin lógica de negocio propia

#### Scenario: Cambio posterior en una regla no altera un checklist ya generado
- **WHEN** un `document_requirement` cambia después de que un proceso ya generó su checklist
- **THEN** los `process_document_checklist_items` ya generados conservan el `tipo_requisito` con el que se generaron
- **AND** solo una nueva resolución del checklist refleja la regla actualizada

### Requirement: Vincular documentos a cualquier entidad de negocio
Un documento SHALL poder vincularse a cualquier entidad mediante `document_links` polimórfico (`linkable_type`/`linkable_id`), sin que el modelo documental dependa de un módulo funcional específico.

#### Scenario: Vincular un documento a un proceso
- **WHEN** se adjunta un documento a un proceso
- **THEN** se crea un `document_link` activo entre ese documento y ese proceso

#### Scenario: Desvincular sin perder historial
- **WHEN** se desvincula un documento de una entidad
- **THEN** el `document_link` correspondiente queda inactivo
- **AND** no se elimina el registro ni el historial de versiones/validaciones del documento
