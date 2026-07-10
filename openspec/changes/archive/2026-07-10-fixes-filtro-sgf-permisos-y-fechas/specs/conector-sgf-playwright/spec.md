## MODIFIED Requirements

### Requirement: Importación selectiva de casos del grupo "Pago operaciones"
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.importar_casos_sgf`, disparar una importación selectiva de los casos pendientes en SGF cuyo grupo actual sea "Pago operaciones" vía el conector Playwright, ejecutada siempre en un Job de cola independiente de la importación masiva, con una sola importación selectiva de este grupo en curso a la vez, sin bloquearse entre sí con la importación masiva.

#### Scenario: Disparar la importación selectiva
- **WHEN** un usuario con el permiso requerido solicita importar los casos del grupo "Pago operaciones"
- **THEN** el sistema encola un Job de importación selectiva y responde de inmediato con el `trabajo_integracion` creado en estado `en_progreso`, con `tipo` `importar_grupo_pago_operaciones`
- **AND** el usuario puede consultar el avance de ese `trabajo_integracion` mediante sondeo (polling)

#### Scenario: La importación selectiva no bloquea ni es bloqueada por la importación masiva
- **WHEN** un usuario solicita importar los casos del grupo "Pago operaciones" mientras existe un `trabajo_integracion` de importación masiva (`tipo` `importar_pendientes`) en `en_progreso`
- **THEN** el sistema encola normalmente el nuevo Job de importación selectiva, sin considerar la importación masiva en curso como un bloqueo

#### Scenario: Ya hay una importación selectiva de este grupo en curso
- **WHEN** un usuario solicita importar los casos del grupo "Pago operaciones" mientras ya existe un `trabajo_integracion` de importación selectiva (`tipo` `importar_grupo_pago_operaciones`) en `en_progreso` dentro de su umbral de detección de huérfanos
- **THEN** el sistema no encola un nuevo Job
- **AND** informa al usuario que ya hay una importación de ese grupo en curso, señalando su `trabajo_integracion`

#### Scenario: El conector Playwright confía en el filtro nativo de la Bandeja y registra los grupos observados
- **WHEN** el Job de importación selectiva se ejecuta
- **THEN** el conector Playwright selecciona "Pago Operaciones" en el filtro "Grupo" del formulario "Buscar" de la Bandeja, fija el rango de fechas (un mes atrás hasta hoy) y solo entonces lee las filas resultantes
- **AND** crea un `snapshot_datos_externo` por cada fila que devuelve el filtro nativo, sin volver a filtrar por la columna "Grupo Actual" — ese es un campo distinto (el paso donde está parado el proceso) que no tiene por qué coincidir con el grupo filtrado, y descartarlo eliminaba filas legítimas (verificado en corrida real 2026-07-10)
- **AND** registra en el detalle del paso `pagina_bandeja_N` los valores distintos de `grupo_actual` observados (`grupos_actuales`), como trazabilidad para diagnosticar cualquier fila inesperada devuelta por el filtro nativo
- **AND** al finalizar, actualiza el `trabajo_integracion` a estado `completado` con el total de filas de ese grupo procesadas

#### Scenario: El conector Playwright falla antes de completar la respuesta
- **WHEN** el conector Playwright falla antes de completar la respuesta de la importación selectiva
- **THEN** el sistema no guarda ningún `snapshot_datos_externo` parcial de esa corrida
- **AND** registra el `trabajo_integracion` en estado `error` con el detalle de la falla
- **AND** permite a un usuario autorizado disparar un nuevo intento
