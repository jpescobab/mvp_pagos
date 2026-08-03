## MODIFIED Requirements

### Requirement: Vínculo opcional entre una OC y un proceso de adquisición
El sistema SHALL permitir vincular y desvincular manualmente una `orden_compra_mercado_publico` a un `proceso_adquisicion` existente y, opcionalmente, a un `Contrato` existente, sin que ninguno de esos vínculos dispare ninguna transición de workflow.

#### Scenario: Vincular una OC a un proceso de adquisición
- **WHEN** un usuario con el permiso requerido vincula una `orden_compra_mercado_publico` a un `proceso_adquisicion` existente
- **THEN** el sistema guarda la referencia y registra la acción en auditoría
- **AND** el estado del `Proceso` del `proceso_adquisicion` no cambia como consecuencia de este vínculo

#### Scenario: Desvincular una OC de un proceso de adquisición
- **WHEN** un usuario desvincula una `orden_compra_mercado_publico` de su `proceso_adquisicion`
- **THEN** el sistema quita la referencia y registra la acción en auditoría

#### Scenario: Vincular una OC a un Contrato
- **WHEN** un usuario con el permiso requerido vincula una `orden_compra_mercado_publico` a un `Contrato` existente
- **THEN** el sistema guarda la referencia en `contrato_id` y registra la acción en auditoría
- **AND** el estado del `Proceso` del `Contrato` no cambia como consecuencia de este vínculo

#### Scenario: Desvincular una OC de un Contrato
- **WHEN** un usuario desvincula una `orden_compra_mercado_publico` de su `Contrato`
- **THEN** el sistema quita la referencia y registra la acción en auditoría

#### Scenario: OC sin vínculo
- **WHEN** se consulta una `orden_compra_mercado_publico` que nunca fue vinculada a un `proceso_adquisicion` ni a un `Contrato`
- **THEN** el sistema la muestra sin proceso de adquisición ni contrato asociado, sin error
