## ADDED Requirements

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
