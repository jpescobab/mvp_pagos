## ADDED Requirements

### Requirement: El cronograma conserva fecha y hora reales de cada etapa
El sistema SHALL conservar la fecha y hora completas que entrega Mercado Público para cada hito del cronograma (`FechaCreacion`, `FechaEnvio`, `FechaAceptacion`, `FechaCancelacion`), sin truncarlas a solo el día. `fecha_emision` queda fuera de este requisito porque su columna es de tipo fecha (sin hora) por diseño.

#### Scenario: La API entrega fecha y hora de un hito
- **WHEN** el payload de Mercado Público incluye un hito de `Fechas` con fecha y hora (por ejemplo `FechaAceptacion`)
- **THEN** el sistema guarda ese hito en el cronograma con su fecha y hora completas, sin recortar la hora

#### Scenario: La API entrega solo fecha sin hora
- **WHEN** el payload de Mercado Público incluye un hito de `Fechas` sin componente de hora
- **THEN** el sistema guarda el valor tal como lo entrega la API, sin inventar ni completar una hora que no fue informada
