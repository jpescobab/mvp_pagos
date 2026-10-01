# Spec: workflow-core

## Purpose

Motor de workflow propio que gobierna todo cambio de estado de cualquier proceso de negocio futuro, exclusivamente a través de `TransicionWorkflowService::execute()`.
## Requirements
### Requirement: Controlar transiciones mediante servicio central
Todo cambio de estado de un proceso SHALL pasar por `TransicionWorkflowService::execute()`. Ningún controlador, job, seeder o componente React SHALL cambiar el estado de un proceso directamente.

#### Scenario: Ejecutar transición válida
- **WHEN** un usuario con permiso ejecuta una transición permitida desde el estado actual, con los documentos requeridos cargados y validados
- **THEN** el sistema cambia el estado interno del proceso
- **AND** registra el historial en `historial_transiciones_workflow`
- **AND** cierra las tareas asociadas a esa transición
- **AND** registra auditoría mediante `AuditLogger`
- **AND** notifica a los responsables de las tareas abiertas del proceso

#### Scenario: Bloquear transición por documento faltante
- **WHEN** un usuario intenta ejecutar una transición que exige un tipo documental para el cual el proceso no tiene un documento vinculado y validado
- **THEN** el sistema bloquea la transición
- **AND** informa cuáles tipos documentales faltan
- **AND** no modifica el estado del proceso

#### Scenario: Bloquear transición sin permiso
- **WHEN** un usuario sin el permiso requerido por la transición intenta ejecutarla
- **THEN** el sistema bloquea la transición y no modifica el estado del proceso

#### Scenario: Bloquear transición no permitida desde el estado actual
- **WHEN** se intenta ejecutar una transición cuyo estado de origen no coincide con el estado actual del proceso
- **THEN** el sistema bloquea la transición y no modifica el estado del proceso

#### Scenario: Bloquear transición si el workflow está inactivo
- **WHEN** la definición de workflow del proceso tiene `activo = false`
- **THEN** el sistema bloquea cualquier transición sobre ese proceso

### Requirement: Modelar procesos de forma genérica
Un `Proceso` SHALL poder asociarse a cualquier entidad de negocio futura mediante una relación polimórfica (`sujeto_type`/`sujeto_id`), sin que workflow-core dependa de ningún módulo funcional específico.

#### Scenario: Crear un proceso para una entidad de negocio
- **WHEN** se crea un proceso para una entidad de negocio en el estado inicial de un workflow activo
- **THEN** el proceso queda asociado a esa entidad mediante `sujeto_type`/`sujeto_id`
- **AND** queda en el estado marcado como `es_inicial` de su workflow

### Requirement: Emitir un evento tras cada transición confirmada
`TransicionWorkflowService::execute()` SHALL emitir un evento `TransicionWorkflowEjecutada` (con el proceso, el código de la transición y el usuario) una vez confirmada la transacción de la transición, de modo que los dominios funcionales puedan reaccionar sin que el core de workflow los conozca. Una falla en un listener SHALL NOT revertir ni impedir la transición ya confirmada.

#### Scenario: Transición confirmada
- **WHEN** una transición se ejecuta correctamente a través del servicio central
- **THEN** se emite `TransicionWorkflowEjecutada` con el proceso, el código de la transición y el usuario, después de confirmar la transacción

#### Scenario: Transición rechazada
- **WHEN** una transición es rechazada (no permitida, sin permiso, documentos faltantes o proceso cerrado)
- **THEN** no se emite el evento

#### Scenario: Un listener falla
- **WHEN** un listener de `TransicionWorkflowEjecutada` lanza una excepción
- **THEN** la transición permanece confirmada y el estado del proceso no se revierte

