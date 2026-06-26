## ADDED Requirements

### Requirement: Sembrar centros financieros y centros de costo reales
El seeder institucional SHALL poblar los centros financieros y centros de costo reales de la jurisdicción inicial (`14`, Zonal Coyhaique), resolviendo cada centro de costo a su centro financiero por `codigo` (no por id literal).

#### Scenario: Sembrar centros financieros de la jurisdicción inicial
- **WHEN** se ejecuta el seeder institucional
- **THEN** existen los 6 centros financieros reales de la jurisdicción `14` (códigos `1400`, `1401`, `1402`, `1431`, `1451`, `1471`)

#### Scenario: Sembrar centros de costo asociados a su centro financiero correcto
- **WHEN** se ejecuta el seeder institucional
- **THEN** existen los 31 centros de costo reales
- **AND** cada centro de costo pertenece al centro financiero correspondiente a su `codigo` de origen
