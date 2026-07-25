## ADDED Requirements

### Requirement: Un proceso de adquisición se puede actualizar solo mientras está en borrador

El sistema SHALL permitir actualizar los datos de un `proceso_adquisicion` (código, modalidad, centro de costo, proveedor, monto, objeto) únicamente mientras su `Proceso` asociado esté en el estado `borrador`. Si el estado actual no es `borrador`, el sistema SHALL rechazar la actualización con una excepción de dominio y no modificar ningún dato. La actualización SHALL validar que la modalidad destino exista y esté activa, igual que la creación. La actualización SHALL ejecutarse dentro de una transacción y, cuando cambie la modalidad o el monto, SHALL sincronizar los campos `modalidad_id` y `monto` del `Proceso` asociado, de modo que el checklist documental —que se resuelve leyendo esos campos desde el `Proceso`— refleje los nuevos valores en su próxima resolución.

#### Scenario: Actualizar un proceso en borrador

- **WHEN** se actualiza un `proceso_adquisicion` cuyo `Proceso` está en estado `borrador`, con una modalidad activa
- **THEN** el sistema guarda los nuevos valores del `proceso_adquisicion`
- **AND** sincroniza `modalidad_id` y `monto` en el `Proceso` asociado

#### Scenario: Rechazar la actualización fuera de borrador

- **WHEN** se intenta actualizar un `proceso_adquisicion` cuyo `Proceso` ya no está en estado `borrador` (por ejemplo, `en_revision` o `publicada`)
- **THEN** el sistema rechaza la operación con una excepción de dominio
- **AND** no modifica ningún dato del `proceso_adquisicion` ni de su `Proceso`

#### Scenario: Rechazar la actualización con una modalidad inactiva

- **WHEN** se intenta actualizar un `proceso_adquisicion` en `borrador` referenciando una modalidad inexistente o inactiva
- **THEN** el sistema rechaza la operación
- **AND** no modifica ningún dato

#### Scenario: Cambiar la modalidad re-resuelve el checklist

- **WHEN** se actualiza la modalidad de un `proceso_adquisicion` en `borrador` y luego se resuelve nuevamente su checklist documental
- **THEN** el checklist refleja los requisitos de la nueva modalidad, no los de la anterior
