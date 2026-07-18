## ADDED Requirements

### Requirement: Capturar el número de traspaso de la Bandeja SGF
El conector Playwright de SGF SHALL capturar el valor de la columna "N° traspaso" de la Bandeja de procesos y exponerlo en el payload crudo de cada fila, para que el importer pueda conservarlo como referencia del caso. La captura SHALL identificar la columna por su encabezado de texto normalizado (independiente de su posición), reutilizando el mecanismo de mapeo de columnas ya existente.

#### Scenario: La fila de la Bandeja incluye número de traspaso
- **WHEN** el conector Playwright lee una fila de la Bandeja de SGF cuya columna "N° traspaso" tiene un valor
- **THEN** el payload crudo de esa fila incluye ese valor bajo la clave del número de traspaso

#### Scenario: La fila de la Bandeja no tiene número de traspaso
- **WHEN** el conector Playwright lee una fila cuya columna "N° traspaso" está vacía o ausente
- **THEN** el payload crudo de esa fila se genera sin fallar, dejando el número de traspaso vacío o ausente
