## MODIFIED Requirements

### Requirement: Mantener catálogo de tipos documentales reutilizable
El sistema SHALL mantener un catálogo de `tipos_documento` (código único, nombre, si es obligatorio por defecto, activo) reutilizable por cualquier matriz de requisitos de cualquier módulo funcional futuro.

#### Scenario: Tipo documental disponible para reglas
- **WHEN** se crea una regla de requisito documental en `requisitos_documentales`
- **THEN** debe referenciar un `tipo_documento` activo del catálogo

### Requirement: Versionar documentos con trazabilidad de validación
Todo documento cargado al expediente SHALL quedar asociado a un tipo documental, conservar todas sus versiones (`versiones_documento`) y registrar un historial inmutable de eventos de validación (`validaciones_documento`). El estado vigente de un documento es el del evento de validación más reciente.

#### Scenario: Subir una nueva versión de un documento
- **WHEN** se carga un archivo para un documento existente
- **THEN** se crea una nueva fila en `versiones_documento`
- **AND** las versiones anteriores no se eliminan

#### Scenario: Validar un documento
- **WHEN** un usuario autorizado valida o rechaza un documento
- **THEN** se registra un nuevo evento en `validaciones_documento` con el resultado y la observación
- **AND** el estado vigente del documento pasa a ser el de ese evento

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

### Requirement: Vincular documentos a cualquier entidad de negocio
Un documento SHALL poder vincularse a cualquier entidad mediante `vinculos_documento` polimórfico (`vinculable_type`/`vinculable_id`), sin que el modelo documental dependa de un módulo funcional específico.

#### Scenario: Vincular un documento a un proceso
- **WHEN** se adjunta un documento a un proceso
- **THEN** se crea un `vinculo_documento` activo entre ese documento y ese proceso

#### Scenario: Desvincular sin perder historial
- **WHEN** se desvincula un documento de una entidad
- **THEN** el `vinculo_documento` correspondiente queda inactivo
- **AND** no se elimina el registro ni el historial de versiones/validaciones del documento
