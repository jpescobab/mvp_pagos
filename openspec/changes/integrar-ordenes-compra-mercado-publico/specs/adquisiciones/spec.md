## MODIFIED Requirements

### Requirement: Integración con Mercado Público como origen de evidencia, no de gobierno
El sistema SHALL permitir que una Orden de Compra de Mercado Público (capability `ordenes-compra-mercado-publico`) se vincule opcionalmente a un `proceso_adquisicion` existente, como evidencia externa trazable. Mercado Público SHALL NOT gobernar el workflow, los estados, los responsables ni las unidades internas de ningún `proceso_adquisicion`: esa vinculación es únicamente informativa y no dispara transiciones de `TransicionWorkflowService`. Los `proceso_adquisicion` SHALL seguir creándose y transicionando exclusivamente por los mecanismos internos ya definidos, con o sin una OC vinculada.

#### Scenario: Crear un proceso de adquisición sin OC vinculada
- **WHEN** se crea un `proceso_adquisicion`
- **THEN** no se exige ni se genera ningún vínculo con una `orden_compra_mercado_publico`

#### Scenario: Vincular una OC no altera el workflow del proceso
- **WHEN** se vincula una `orden_compra_mercado_publico` a un `proceso_adquisicion` existente
- **THEN** el estado del `Proceso` de ese `proceso_adquisicion` permanece sin cambios
- **AND** la vinculación queda registrada en auditoría como una acción independiente del workflow

#### Scenario: La verificación de una OC contra Mercado Público no requiere un proceso de adquisición
- **WHEN** un usuario busca, verifica o guarda una `orden_compra_mercado_publico` sin vincularla a ningún `proceso_adquisicion`
- **THEN** el sistema completa la operación normalmente, dejando el vínculo pendiente para más adelante
