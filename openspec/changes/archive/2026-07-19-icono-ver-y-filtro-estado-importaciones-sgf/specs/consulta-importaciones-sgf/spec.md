## MODIFIED Requirements

### Requirement: Listar las corridas de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, un listado paginado de los `trabajos_integracion` del sistema externo `SGF`, ordenado del más reciente al más antiguo, con su tipo (verificación puntual o importación masiva), mecanismo, quién lo inició, fecha de inicio y fin, total de elementos y estado. El sistema SHALL permitir filtrar ese listado mediante un término de búsqueda opcional que coincida con el tipo de corrida o con el nombre del usuario que la inició, conservando el resto de corridas fuera de ese filtro cuando no se proporciona término. El sistema SHALL, por defecto (sin un filtro de estado explícito), excluir del listado los `trabajos_integracion` en estado `completado`, mostrando únicamente los que aún requieren atención (`en_progreso`, `error` o `huerfano`). El sistema SHALL permitir ver todos los estados mediante un filtro explícito de "todos", y SHALL permitir filtrar por un estado puntual del trabajo de importación. El filtro de estado y el término de búsqueda SHALL combinarse entre sí (ambos aplican a la vez) cuando se usan juntos.

#### Scenario: Listar corridas de importación por defecto
- **WHEN** un usuario autenticado visita el listado de importaciones SGF sin término de búsqueda y sin filtro de estado
- **THEN** la respuesta incluye los `trabajos_integracion` del sistema externo `SGF` cuyo estado no es `completado`, paginados, ordenados del más reciente al más antiguo, cada uno con su tipo, usuario que lo inició, fechas de inicio/fin, total de elementos y estado

#### Scenario: Ver todos los estados explícitamente
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con el filtro de estado en "todos"
- **THEN** la respuesta incluye los `trabajos_integracion` de SGF de cualquier estado, incluidos los `completado`, paginados y ordenados del más reciente al más antiguo

#### Scenario: Filtrar por un estado puntual
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con el filtro de estado fijado a un valor puntual (`en_progreso`, `completado`, `error` o `huerfano`)
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` de SGF cuyo estado coincide exactamente con ese valor

#### Scenario: Filtrar corridas por término de búsqueda
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que coincide con el tipo de corrida o con el nombre del usuario que la inició
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` de SGF cuyo tipo o usuario que los inició coincide con el término, paginados igual que el listado sin filtrar, aplicando también el filtro de estado vigente (por defecto o explícito)

#### Scenario: Búsqueda sin resultados
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que no coincide con ningún `trabajo_integracion` de SGF
- **THEN** la respuesta incluye un listado vacío, sin error
