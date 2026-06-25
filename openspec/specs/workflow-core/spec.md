# Spec: workflow-core

## Requirement: Controlar transiciones mediante servicio central

Todo cambio de estado debe pasar por `WorkflowTransitionService::execute()`.

### Scenario: Ejecutar transición válida

Given existe un proceso en estado actual
And el usuario tiene permiso para ejecutar la transición
And los documentos requeridos están completos
When el usuario ejecuta la transición
Then el sistema cambia el estado interno
And registra historial
And cierra o crea tareas según corresponda
And registra auditoría
And notifica a los responsables

### Scenario: Bloquear transición inválida

Given falta un documento obligatorio
When el usuario intenta ejecutar una transición que exige ese documento
Then el sistema bloquea la transición
And informa la causa
And no modifica el estado del proceso
