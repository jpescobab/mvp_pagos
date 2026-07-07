# Spec: integraciones-api-browser-automation

## Purpose

Capa transversal para registrar toda integración con sistemas externos (API primero) y, solo como respaldo autorizado y trazado, automatizaciones Playwright. No gobierna workflow ni reemplaza la lógica de los sistemas oficiales (SGF, CGU, BancoEstado, SII, CMF, Mercado Público); es evidencia y trazabilidad de integración, consumible por los módulos funcionales.

## Requirements

### Requirement: Catalogar sistemas externos
El sistema SHALL mantener un catálogo de sistemas externos (`sistemas_externos`) con su código único, mecanismo de integración vigente (`api`, `playwright` o `manual`) y estado activo, como base de cualquier integración.

#### Scenario: Registrar un sistema externo
- **WHEN** se define un nuevo sistema externo con su código y mecanismo de integración
- **THEN** se crea un `sistema_externo` único por código
- **AND** queda disponible como referencia para `trabajos_integracion`, `solicitudes_api_externas`, `snapshots_datos_externos` y conectores Playwright

### Requirement: Registrar cada corrida de integración
El sistema SHALL registrar cada corrida de integración (importación, consulta o sincronización) contra un sistema externo en `trabajos_integracion`, incluyendo su mecanismo (`api`/`playwright`), quién o qué la inició, su estado y resultado.

#### Scenario: Iniciar y cerrar una corrida de integración
- **WHEN** se inicia una corrida de integración contra un `sistema_externo`
- **THEN** se crea un `trabajo_integracion` con su mecanismo, momento de inicio y responsable
- **AND** al finalizar la corrida se registra su estado final (`completado` o `fallido`) y momento de cierre

### Requirement: Registrar cada llamada API externa
El sistema SHALL registrar cada llamada API a un sistema externo en `solicitudes_api_externas`, incluyendo endpoint, payload enviado, payload recibido, código de respuesta y errores, opcionalmente asociada a un `trabajo_integracion`.

#### Scenario: Registrar una llamada API exitosa
- **WHEN** se ejecuta una llamada API a un `sistema_externo`
- **THEN** se crea una `solicitud_api_externa` con el endpoint, payload enviado y payload recibido
- **AND** se registra el código de respuesta HTTP y el estado (`exitoso` o `fallido`)

#### Scenario: Registrar una llamada API fallida
- **WHEN** una llamada API a un sistema externo falla o devuelve error
- **THEN** se crea una `solicitud_api_externa` con estado `fallido` y el detalle del error
- **AND** no se interrumpe el registro de la corrida (`trabajo_integracion`) asociada

### Requirement: Conservar snapshot inmutable de datos externos
El sistema SHALL conservar, por cada dato externo relevante para gestión, cálculo o informe, un `snapshot_datos_externos` inmutable con su payload crudo, payload normalizado, hash de contenido y método de captura (`api`, `playwright`, `manual`, `csv` o `excel`), sin sobrescribir snapshots anteriores de la misma referencia externa.

#### Scenario: Capturar snapshot de un dato externo
- **WHEN** se recibe un dato externo relevante desde una llamada API o una automatización Playwright
- **THEN** se crea un `snapshot_datos_externos` con `payload_crudo`, `payload_normalizado`, `hash` y método de captura
- **AND** puede vincularse de forma polimórfica a un caso interno mediante `vinculable`

#### Scenario: Recapturar la misma referencia externa crea un snapshot nuevo
- **WHEN** se vuelve a capturar un dato externo cuya referencia externa ya tiene un `snapshot_datos_externos` previo
- **THEN** se crea un nuevo `snapshot_datos_externos`
- **AND** el snapshot anterior no se modifica ni se elimina

### Requirement: Autorizar explícitamente cada conector Playwright
El sistema SHALL exigir que cada `conector_automatizacion_navegador` esté asociado a un `sistema_externo`, activo y con autorización explícita (usuario y fecha) antes de permitir que se inicie cualquier `ejecucion_automatizacion_navegador` sobre él.

#### Scenario: Iniciar una ejecución sobre un conector autorizado
- **WHEN** se inicia una `ejecucion_automatizacion_navegador` sobre un `conector_automatizacion_navegador` activo y autorizado
- **THEN** se crea la `ejecucion_automatizacion_navegador` con su estado inicial y responsable

#### Scenario: Rechazar una ejecución sobre un conector no autorizado o inactivo
- **WHEN** se intenta iniciar una `ejecucion_automatizacion_navegador` sobre un conector inactivo o sin autorización registrada
- **THEN** el sistema rechaza el inicio de la ejecución
- **AND** no se crea ninguna `ejecucion_automatizacion_navegador`

### Requirement: No almacenar credenciales ni cookies de automatización
El sistema SHALL NOT almacenar el valor real de credenciales ni cookies de automatización en `perfiles_autenticacion_navegador` ni en ninguna otra tabla; SHALL almacenar únicamente una referencia (almacén y clave) a dónde vive el secreto real.

#### Scenario: Registrar un perfil de autenticación de automatización
- **WHEN** se registra un `perfil_autenticacion_navegador` para un conector Playwright
- **THEN** se guarda el almacén de secretos y la referencia a la clave del secreto
- **AND** no se guarda contraseña, token ni cookie en texto plano en la base de datos

### Requirement: Registrar pasos y artifacts de cada corrida Playwright
El sistema SHALL registrar, para cada `ejecucion_automatizacion_navegador`, sus pasos ejecutados (`pasos_automatizacion_navegador`) y la evidencia capturada (`artefactos_automatizacion_navegador`) como registros append-only, sin evadir controles de acceso, MFA ni CAPTCHA.

#### Scenario: Registrar pasos y evidencia de una ejecución
- **WHEN** una `ejecucion_automatizacion_navegador` ejecuta sus pasos
- **THEN** cada paso se registra como un `paso_automatizacion_navegador` con su acción, estado y orden
- **AND** la evidencia relevante (capturas, trazas) se registra como `artefacto_automatizacion_navegador` vinculado a la ejecución o al paso correspondiente

### Requirement: Vincular varios documentos a un mismo snapshot de datos externos
El sistema SHALL permitir vincular varios documentos del expediente (`Documento`) a un mismo `snapshot_datos_externo` mediante una tabla de unión (`snapshots_datos_externos_documentos`), independiente del `vinculable` polimórfico único que ya usa `snapshot_datos_externo` para su entidad interna asociada.

#### Scenario: Un snapshot con varios documentos entregados por el sistema externo
- **WHEN** un `snapshot_datos_externo` se genera a partir de datos que incluyen uno o más documentos
- **THEN** cada documento se crea o resuelve como `Documento`/`VersionDocumento` del expediente
- **AND** se crea un registro en `snapshots_datos_externos_documentos` que vincula cada documento a ese `snapshot_datos_externo`

#### Scenario: Un snapshot sin documentos asociados
- **WHEN** un `snapshot_datos_externo` se genera a partir de datos que no incluyen ningún documento
- **THEN** no se crea ningún registro en `snapshots_datos_externos_documentos` para ese snapshot
