## MODIFIED Requirements

### Requirement: Importar masivamente casos pendientes de SGF bajo demanda
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.importar_casos_sgf`, disparar una importación masiva de los casos pendientes en SGF vía el conector Playwright, ejecutada siempre en un Job de cola independientemente de la cantidad de filas que resulten, con una sola importación masiva en curso a la vez.

#### Scenario: Disparar una importación masiva
- **WHEN** un usuario con el permiso requerido solicita importar los casos pendientes de SGF
- **THEN** el sistema encola un Job de importación masiva y responde de inmediato con el `trabajo_integracion` creado en estado `en_progreso`
- **AND** el usuario puede consultar el avance de ese `trabajo_integracion` mediante sondeo (polling)

#### Scenario: Ya hay una importación masiva en curso
- **WHEN** un usuario solicita importar los casos pendientes de SGF mientras ya existe un `trabajo_integracion` de importación masiva en `en_progreso` dentro de su umbral de detección de huérfanos
- **THEN** el sistema no encola un nuevo Job
- **AND** informa al usuario que ya hay una importación en curso, señalando su `trabajo_integracion`

#### Scenario: La importación masiva completa registra un snapshot por fila
- **WHEN** el Job de importación masiva recibe del conector Playwright las filas de los casos pendientes
- **THEN** el sistema crea un `snapshot_datos_externo` por cada fila recibida, vinculado al mismo `trabajo_integracion`
- **AND** al finalizar, actualiza el `trabajo_integracion` a estado `completado` con el total de filas procesadas

#### Scenario: El conector Playwright falla a mitad de la importación masiva
- **WHEN** el conector Playwright falla antes de completar la respuesta de la importación masiva
- **THEN** el sistema no guarda ningún `snapshot_datos_externo` parcial de esa corrida
- **AND** registra el `trabajo_integracion` en estado `error` con el detalle de la falla
- **AND** permite a un usuario autorizado disparar un nuevo intento

#### Scenario: Un trabajo de importación masiva huérfano permite un nuevo intento
- **WHEN** un usuario solicita importar los casos pendientes de SGF mientras el `trabajo_integracion` de importación masiva existente ya fue marcado (o se detecta en ese momento) como `huerfano` por la capa transversal de integraciones
- **THEN** el sistema encola un nuevo Job de importación masiva normalmente, como si no existiera ninguna importación en curso

#### Scenario: Usuario sin permiso intenta importar masivamente
- **WHEN** un usuario sin el permiso `pago_proveedores.importar_casos_sgf` intenta disparar una importación masiva
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`
