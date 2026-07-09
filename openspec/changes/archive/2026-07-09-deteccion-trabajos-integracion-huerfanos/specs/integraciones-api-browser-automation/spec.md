## ADDED Requirements

### Requirement: Detectar y marcar automáticamente trabajos de integración huérfanos
El sistema SHALL detectar un `trabajo_integracion` en estado `en_progreso` cuyo tiempo transcurrido desde `iniciado_en` supere el umbral configurado para su `tipo`, y SHALL marcarlo como `huerfano` (con `finalizado_en` y un mensaje de error explícito indicando detección automática), sin requerir intervención manual en la base de datos. Esta detección SHALL aplicar de forma genérica a cualquier `trabajo_integracion`, independientemente del `sistema_externo` o mecanismo (`api`/`playwright`).

#### Scenario: Barrido periódico marca un trabajo huérfano
- **WHEN** el barrido programado se ejecuta y encuentra un `trabajo_integracion` en `en_progreso` cuyo `iniciado_en` supera el umbral configurado para su `tipo`
- **THEN** el sistema actualiza ese `trabajo_integracion` a estado `huerfano`, con `finalizado_en` y un mensaje de error que indica que fue detectado automáticamente por inactividad
- **AND** no modifica ningún `trabajo_integracion` cuyo tiempo transcurrido siga por debajo del umbral de su `tipo`

#### Scenario: Un trabajo huérfano no bloquea un nuevo intento
- **WHEN** un usuario autorizado intenta iniciar una nueva corrida del mismo `tipo` de integración mientras el `trabajo_integracion` existente ya fue marcado (o se detecta en ese momento) como `huerfano`
- **THEN** el sistema permite iniciar la nueva corrida sin bloquear por la guarda de "ya hay uno en curso"

#### Scenario: Un trabajo en_progreso legítimo dentro del umbral sigue bloqueando
- **WHEN** un usuario autorizado intenta iniciar una nueva corrida del mismo `tipo` mientras existe un `trabajo_integracion` en `en_progreso` dentro de su umbral configurado
- **THEN** el sistema no inicia una nueva corrida
- **AND** informa al usuario que ya hay una en curso, igual que antes de esta detección

#### Scenario: Estado huérfano distinguible de un error de negocio real
- **WHEN** se lista o se consulta el detalle de un `trabajo_integracion`
- **THEN** un `trabajo_integracion` en estado `huerfano` se distingue visualmente de uno en estado `error`, para no confundir un fallo de negocio real (ej. SGF rechazó la operación) con un proceso que murió sin poder reportar por qué
