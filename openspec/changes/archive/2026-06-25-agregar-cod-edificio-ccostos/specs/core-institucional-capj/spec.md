## ADDED Requirements

### Requirement: Asociar opcionalmente un código de edificio a un centro de costo
La tabla `ccostos` SHALL permitir registrar un `cod_edificio` opcional para los centros de costo que correspondan a un inmueble físico concreto.

#### Scenario: Registrar un centro de costo sin código de edificio
- **WHEN** se registra un centro de costo sin especificar `cod_edificio`
- **THEN** el registro se guarda correctamente con `cod_edificio` en `null`

#### Scenario: Registrar un centro de costo con código de edificio
- **WHEN** se registra un centro de costo especificando un `cod_edificio`
- **THEN** el registro guarda ese valor asociado al centro de costo
