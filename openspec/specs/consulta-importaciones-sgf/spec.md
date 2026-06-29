# Spec: consulta-importaciones-sgf

## Purpose

Exponer, de solo lectura, el historial de corridas de importación SGF (`ImportacionSgf`) y los snapshots que cada una produjo, para que sea consultable en vez de solo verificable directamente en la base de datos.

## Requirements

### Requirement: Listar las corridas de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, un listado paginado de las `ImportacionSgf` existentes, ordenado de la más reciente a la más antigua, con su fuente, quién la inició, fecha de inicio y fin, total de filas y estado.

#### Scenario: Listar corridas de importación
- **WHEN** un usuario autenticado visita el listado de importaciones SGF
- **THEN** la respuesta incluye las `ImportacionSgf` paginadas, ordenadas de la más reciente a la más antigua, cada una con su fuente, usuario que la inició, fechas de inicio/fin, total de filas y estado

### Requirement: Ver el detalle de una corrida de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, el detalle de una `ImportacionSgf` junto con todos los `snapshots_sgf` que produjo.

#### Scenario: Ver los snapshots producidos por una corrida
- **WHEN** un usuario autenticado abre el detalle de una `ImportacionSgf`
- **THEN** la respuesta incluye todos los `snapshots_sgf` asociados a esa corrida, cada uno con su `sgf_id`, hash y fecha de captura
