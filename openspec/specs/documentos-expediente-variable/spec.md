# Spec: documentos-expediente-variable

## Purpose

Modelo documental real del expediente: catálogo de tipos documentales, documentos versionados con trazabilidad de validación, y una matriz de requisitos configurable por workflow/modalidad/monto/estado que el backend resuelve en un checklist por proceso. React nunca hardcodea requisitos documentales.

## Requirements

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


### Requirement: Subir y vincular un documento a un proceso vía HTTP
El sistema SHALL exponer un endpoint autorizado (`documentos.gestionar`) para subir un archivo y vincularlo a un `Proceso`, creando un `Documento`, su primera `VersionDocumento` y un `VinculoDocumento` activo en una sola operación transaccional, sin depender de ningún módulo funcional específico.

#### Scenario: Subir un documento válido
- **WHEN** un usuario con el permiso `documentos.gestionar` sube un archivo válido (tipo MIME permitido, tamaño dentro del límite) junto con un `tipo_documento_id` activo, para un `Proceso` existente
- **THEN** se crea un `Documento` con su `VersionDocumento` número 1 y un `VinculoDocumento` activo asociado a ese `Proceso`
- **AND** el archivo se almacena en un disco no público

#### Scenario: Rechazar un archivo inválido
- **WHEN** se intenta subir un archivo con un tipo MIME no permitido o que excede el tamaño máximo
- **THEN** el sistema rechaza la operación sin crear ningún registro

#### Scenario: Usuario sin permiso intenta subir
- **WHEN** un usuario sin el permiso `documentos.gestionar` intenta subir un documento
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

### Requirement: Listar y descargar los documentos vinculados a un proceso
El sistema SHALL incluir los documentos vinculados activos de un `Proceso` (tipo, nombre de archivo, estado vigente) en la misma respuesta que expone su checklist documental, y SHALL exponer un endpoint de descarga protegido por autenticación, sin URLs públicas directas al archivo.

#### Scenario: Ver documentos vinculados en el detalle de un proceso
- **WHEN** un usuario abre el detalle de un proceso (de cualquier módulo) que tiene documentos vinculados activos
- **THEN** la respuesta incluye la lista de esos documentos junto al checklist

#### Scenario: Descargar un documento vinculado
- **WHEN** un usuario autenticado solicita la descarga de un documento vinculado a un proceso
- **THEN** el sistema sirve el archivo desde el disco privado
- **AND** un usuario no autenticado no puede acceder al archivo

### Requirement: Desvincular un documento sin perder su historial
El sistema SHALL permitir desvincular un documento de un proceso marcando su `VinculoDocumento` como inactivo, sin eliminar el `Documento`, sus `VersionDocumento` ni su historial de validaciones.

#### Scenario: Desvincular un documento
- **WHEN** un usuario con el permiso `documentos.gestionar` desvincula un documento de un proceso
- **THEN** el `VinculoDocumento` correspondiente queda `activo = false`
- **AND** el `Documento` y sus versiones permanecen en la base de datos
