# Spec: conector-sgf-playwright

## Purpose

Conector Playwright de SGF: permite verificar puntualmente un caso o importar masivamente los casos pendientes de SGF bajo demanda, reutilizando la capa transversal de integraciones (`trabajos_integracion`, `snapshots_datos_externos`, `ejecucion_automatizacion_navegador`) en vez de tablas propias de SGF.

## Requirements

### Requirement: Verificar puntualmente un caso SGF bajo demanda
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.verificar_caso_sgf`, disparar de forma síncrona la verificación de un único `sgf_id` contra SGF vía el conector Playwright, devolviendo el resultado en la misma respuesta sin requerir Job en cola.

#### Scenario: Verificación puntual encuentra el caso
- **WHEN** un usuario con el permiso requerido solicita verificar un `sgf_id` contra SGF
- **THEN** el sistema invoca el conector Playwright de SGF de forma síncrona
- **AND** si SGF devuelve datos para ese `sgf_id`, el sistema registra el `trabajo_integracion`, la `ejecucion_automatizacion_navegador` con sus pasos, y un `snapshot_datos_externo` con el payload recibido
- **AND** presenta el resultado al usuario en la misma respuesta

#### Scenario: Verificación puntual no encuentra el caso
- **WHEN** un usuario solicita verificar un `sgf_id` que SGF no reconoce
- **THEN** el sistema registra el `trabajo_integracion` y la `ejecucion_automatizacion_navegador` como completados sin resultado
- **AND** no crea ningún `snapshot_datos_externo`
- **AND** informa al usuario que el caso no fue encontrado en SGF

#### Scenario: Usuario sin permiso intenta verificar un caso
- **WHEN** un usuario sin el permiso `pago_proveedores.verificar_caso_sgf` intenta disparar una verificación puntual
- **THEN** el sistema bloquea la operación
- **AND** registra el evento de autorización denegada en `security_audit_logs`

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

### Requirement: Toda operación contra SGF exige el conector Playwright autorizado
El sistema SHALL rechazar cualquier verificación puntual o importación masiva contra SGF si su `conector_automatizacion_navegador` no está activo y autorizado, reutilizando la regla ya existente de `integraciones-api-browser-automation`.

#### Scenario: Conector de SGF no autorizado
- **WHEN** un usuario con permiso solicita verificar o importar casos de SGF mientras el `conector_automatizacion_navegador` de SGF no está autorizado
- **THEN** el sistema rechaza la operación antes de invocar al microservicio Playwright
- **AND** no se crea ningún `trabajo_integracion` ni `ejecucion_automatizacion_navegador`

### Requirement: Contrato HTTP autenticado con el microservicio Playwright de SGF
El sistema SHALL invocar el microservicio `services/sgf-playwright/` únicamente mediante llamadas HTTP autenticadas con una clave interna configurada en `services.sgf_playwright.api_key`, y SHALL tratar cualquier respuesta de error o código HTTP no exitoso como una corrida fallida sin datos parciales guardados.

#### Scenario: El microservicio responde con error de autenticación
- **WHEN** la llamada al microservicio Playwright de SGF responde con un código de autenticación inválida
- **THEN** el sistema registra el `trabajo_integracion` en estado `error`
- **AND** no crea ningún `snapshot_datos_externo`

#### Scenario: El microservicio responde exitosamente
- **WHEN** el microservicio Playwright de SGF responde exitosamente con las filas solicitadas
- **THEN** el sistema registra cada paso de navegación reportado como `paso_automatizacion_navegador`
- **AND** procede a registrar los snapshots correspondientes
