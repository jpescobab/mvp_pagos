## MODIFIED Requirements

### Requirement: Resolver checklist documental por proceso según reglas configurables
El sistema SHALL determinar los documentos requeridos de un proceso según reglas configurables por workflow, modalidad (opcional), tipo de proceso de pago (opcional), rango de monto y estado (opcional) en `requisitos_documentales`, sin que el frontend las hardcodee. Un `requisito_documental` cuyo `TipoDocumento` esté desactivado (`activo = false`) SHALL excluirse de la resolución, sin bloquear la resolución de los demás requisitos aplicables.

#### Scenario: Generar checklist documental
- **WHEN** el usuario abre el expediente de un proceso
- **THEN** el backend resuelve los `requisitos_documentales` aplicables a ese proceso según su workflow, modalidad, tipo de proceso de pago, monto y estado actual
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

#### Scenario: Un requisito con tipo de proceso de pago específico solo aplica a esa clasificación
- **WHEN** se resuelven los `requisitos_documentales` de un proceso cuyo `tipo_proceso_pago_id` está asignado
- **THEN** solo se consideran los requisitos con `tipo_proceso_pago_id` nulo (universales) o igual al del proceso

#### Scenario: Un proceso sin tipo de proceso de pago clasificado solo ve los requisitos universales
- **WHEN** se resuelven los `requisitos_documentales` de un proceso cuyo `tipo_proceso_pago_id` es `null`
- **THEN** solo se consideran los requisitos con `tipo_proceso_pago_id` nulo

#### Scenario: Un requisito con tipo de documento desactivado no aparece en el checklist
- **WHEN** se resuelven los `requisitos_documentales` de un proceso y alguno de ellos referencia un `TipoDocumento` con `activo = false`
- **THEN** ese requisito se excluye del checklist generado
- **AND** los demás requisitos aplicables se resuelven normalmente

### Requirement: Listar y descargar los documentos vinculados a un proceso
El sistema SHALL incluir los documentos vinculados activos de un `Proceso` (tipo, nombre de archivo, estado vigente) en la misma respuesta que expone su detalle, y SHALL exponer un endpoint de descarga protegido por autenticación, sin URLs públicas directas al archivo. El sistema SHALL además exponer un endpoint separado para visualizar el mismo archivo embebido (disposition inline), bajo la misma protección de autenticación, sin forzar su descarga.

#### Scenario: Ver documentos vinculados en el detalle de un proceso
- **WHEN** un usuario abre el detalle de un proceso (de cualquier módulo) que tiene documentos vinculados activos
- **THEN** la respuesta incluye la lista de esos documentos junto al checklist

#### Scenario: Descargar un documento vinculado
- **WHEN** un usuario autenticado solicita la descarga de un documento vinculado a un proceso
- **THEN** el sistema sirve el archivo desde el disco privado
- **AND** un usuario no autenticado no puede acceder al archivo

#### Scenario: Ver un documento vinculado embebido sin forzar la descarga
- **WHEN** un usuario autenticado solicita ver (no descargar) un documento vinculado a un proceso
- **THEN** el sistema sirve el archivo desde el disco privado con una respuesta cuya disposition no es `attachment`, apta para embeberse en un visor
