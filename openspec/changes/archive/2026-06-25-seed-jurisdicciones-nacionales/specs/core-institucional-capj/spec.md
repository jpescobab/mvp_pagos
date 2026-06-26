## ADDED Requirements

### Requirement: Sembrar la lista nacional de jurisdicciones
El seeder institucional SHALL poblar las 20 jurisdicciones reales del Poder Judicial (códigos `00` a `18` y `99`) bajo la institución CAPJ, sin sobrescribir el nombre de jurisdicciones ya sembradas previamente.

#### Scenario: Sembrar las jurisdicciones nacionales
- **WHEN** se ejecuta el seeder institucional
- **THEN** existen las 20 jurisdicciones reales, cada una asociada a la institución CAPJ

#### Scenario: Preservar el nombre de una jurisdicción ya sembrada
- **WHEN** el seeder se ejecuta y la jurisdicción `14` ya existe con un nombre distinto al de la lista de origen
- **THEN** el seeder no sobrescribe el nombre existente
