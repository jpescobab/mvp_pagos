## MODIFIED Requirements

### Requirement: Listar las corridas de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, un listado paginado de los `trabajos_integracion` del sistema externo `SGF`, ordenado del más reciente al más antiguo, con su tipo (verificación puntual, importación masiva o importación selectiva del grupo "Pago operaciones"), mecanismo, quién lo inició, fecha de inicio y fin, total de elementos y estado.

#### Scenario: Listar corridas de importación
- **WHEN** un usuario autenticado visita el listado de importaciones SGF
- **THEN** la respuesta incluye los `trabajos_integracion` del sistema externo `SGF` paginados, ordenados del más reciente al más antiguo, cada uno con su tipo (incluyendo `importar_grupo_pago_operaciones` cuando corresponda), usuario que lo inició, fechas de inicio/fin, total de elementos y estado
