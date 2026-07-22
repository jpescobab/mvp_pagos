## MODIFIED Requirements

### Requirement: Listar las corridas de importación SGF
El sistema SHALL exponer, a cualquier usuario autenticado, un listado paginado de los `trabajos_integracion` del sistema externo `SGF`, ordenado del más reciente al más antiguo, con su tipo (verificación puntual o importación masiva), mecanismo, quién lo inició, fecha de inicio y fin, total de elementos y estado. El sistema SHALL permitir filtrar ese listado mediante un término de búsqueda opcional que coincida con el tipo de corrida o con el nombre del usuario que la inició, conservando el resto de corridas fuera de ese filtro cuando no se proporciona término. El sistema SHALL, por defecto (sin un filtro de estado explícito), mostrar únicamente los `trabajos_integracion` en estado `completado`. El sistema SHALL permitir ver todos los estados mediante un filtro explícito de "todos", ver solo las que aún requieren atención mediante un filtro "no completadas" (`en_progreso`, `error` o `huerfano`), y filtrar por un estado puntual del trabajo de importación. El filtro de estado y el término de búsqueda SHALL combinarse entre sí (ambos aplican a la vez) cuando se usan juntos.

Además, por cada corrida el sistema SHALL incluir un **desglose de etapas del workflow interno**: la cantidad de `caso_pago_proveedor` producidos por esa corrida agrupados por el estado actual de su `Proceso`, cada grupo con el código y el nombre del estado, ordenados según el orden del workflow. El desglose SHALL calcularse en el backend para toda la página sin incurrir en consultas por fila (N+1). Una corrida que no produjo casos SHALL exponer un desglose vacío.

#### Scenario: Listar corridas de importación por defecto muestra solo completadas
- **WHEN** un usuario autenticado visita el listado de importaciones SGF sin término de búsqueda y sin filtro de estado
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` del sistema externo `SGF` en estado `completado`, paginados, ordenados del más reciente al más antiguo, cada uno con su tipo, usuario que lo inició, fechas de inicio/fin, total de elementos, estado y desglose de etapas

#### Scenario: Ver solo las que requieren atención
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con el filtro de estado en "no completadas"
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` de SGF cuyo estado es `en_progreso`, `error` o `huerfano`, paginados y ordenados del más reciente al más antiguo

#### Scenario: Ver todos los estados explícitamente
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con el filtro de estado en "todos"
- **THEN** la respuesta incluye los `trabajos_integracion` de SGF de cualquier estado, incluidos los `completado`, paginados y ordenados del más reciente al más antiguo

#### Scenario: Filtrar por un estado puntual
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con el filtro de estado fijado a un valor puntual (`en_progreso`, `completado`, `error` o `huerfano`)
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` de SGF cuyo estado coincide exactamente con ese valor

#### Scenario: El desglose de etapas refleja las etapas de los casos producidos
- **WHEN** una corrida produjo casos que hoy están en distintas etapas del workflow (por ejemplo, algunos en `en_revision_finanzas` y otros en `en_revision_zonal`)
- **THEN** su desglose incluye un grupo por cada etapa presente, con el código y nombre del estado y la cantidad de casos en esa etapa, ordenados según el orden del workflow

#### Scenario: Una corrida sin casos expone un desglose vacío
- **WHEN** una corrida no produjo ningún `caso_pago_proveedor` (por ejemplo, una corrida en `error` con cero elementos)
- **THEN** su desglose de etapas es vacío

#### Scenario: Filtrar corridas por término de búsqueda
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que coincide con el tipo de corrida o con el nombre del usuario que la inició
- **THEN** la respuesta incluye únicamente los `trabajos_integracion` de SGF cuyo tipo o usuario que los inició coincide con el término, paginados igual que el listado sin filtrar, aplicando también el filtro de estado vigente (por defecto o explícito)

#### Scenario: Búsqueda sin resultados
- **WHEN** un usuario autenticado visita el listado de importaciones SGF con un término de búsqueda que no coincide con ningún `trabajo_integracion` de SGF
- **THEN** la respuesta incluye un listado vacío, sin error

## ADDED Requirements

### Requirement: Eliminar una corrida de importación SGF sin trazabilidad
El sistema SHALL permitir eliminar una corrida de importación SGF (`trabajo_integracion`) únicamente cuando esa corrida **no produjo trazabilidad**: no tiene `snapshots_datos_externos` asociados y no está en estado `en_progreso`. La eliminación SHALL estar gobernada por el permiso `integraciones_sgf.eliminar_importacion`; un usuario sin ese permiso no SHALL poder eliminar ninguna corrida. Al eliminar una corrida elegible, el sistema SHALL borrar el `trabajo_integracion` junto con sus artefactos propios del intento (ejecuciones de automatización de navegador y sus pasos, solicitudes API externas) dentro de una transacción, y SHALL registrar la eliminación en la auditoría de seguridad. El sistema NUNCA SHALL borrar `snapshots_datos_externos`, `caso_pago_proveedor`, sus `Proceso`, ni registros de auditoría preexistentes.

#### Scenario: Eliminar una corrida fallida sin snapshots
- **WHEN** un usuario con el permiso `integraciones_sgf.eliminar_importacion` elimina una corrida en `error` (o `huerfano`) que no tiene snapshots asociados
- **THEN** el `trabajo_integracion` y sus artefactos propios del intento se eliminan
- **AND** queda registrado un evento de auditoría de la eliminación

#### Scenario: No se puede eliminar una corrida que produjo snapshots o casos
- **WHEN** se intenta eliminar una corrida que tiene al menos un `snapshot_datos_externo` asociado (por ejemplo, una `completado` que produjo casos)
- **THEN** la eliminación es rechazada con una explicación y no se borra nada

#### Scenario: No se puede eliminar una corrida en progreso
- **WHEN** se intenta eliminar una corrida en estado `en_progreso`
- **THEN** la eliminación es rechazada y no se borra nada

#### Scenario: Sin permiso no se puede eliminar
- **WHEN** un usuario sin el permiso `integraciones_sgf.eliminar_importacion` intenta eliminar cualquier corrida
- **THEN** la acción es denegada y no se borra nada
