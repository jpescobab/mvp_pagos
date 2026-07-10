## MODIFIED Requirements

### Requirement: Listar las corridas de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, un listado paginado de los `trabajos_integracion` del sistema externo `SGF`, ordenado del más reciente al más antiguo, con su tipo (verificación puntual o importación masiva), mecanismo, quién lo inició, fecha de inicio y fin, total de elementos y estado. El sistema SHALL permitir filtrar ese listado mediante un término de búsqueda opcional que coincida con el tipo de corrida o con el nombre del usuario que la inició, conservando el resto de corridas fuera de ese filtro cuando no se proporciona término.

#### Scenario: Listar corridas de importación
- **WHEN** un usuario autenticado visita el listado de importaciones SGF sin término de búsqueda
- **THEN** la respuesta incluye los `trabajos_integracion` del sistema externo `SGF` paginados, ordenados del más reciente al más antiguo, cada uno con su tipo, usuario que lo inició, fechas de inicio/fin, total de elementos y estado

#### Scenario: Filtrar corridas por término de búsqueda
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que coincide con el tipo de corrida o con el nombre del usuario que la inició
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` de SGF cuyo tipo o usuario que los inició coincide con el término, paginados igual que el listado sin filtrar

#### Scenario: Búsqueda sin resultados
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que no coincide con ningún `trabajo_integracion` de SGF
- **THEN** la respuesta incluye un listado vacío, sin error
