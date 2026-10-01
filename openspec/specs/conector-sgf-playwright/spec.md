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
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.importar_casos_sgf`, disparar una importación masiva de los casos pendientes en SGF vía el conector Playwright, ejecutada siempre en un Job de cola independientemente de la cantidad de filas que resulten, con una sola importación masiva en curso a la vez. La descarga de los documentos adjuntos de un caso SHALL ser resiliente por documento y por caso: que un documento individual o un caso individual falle no SHALL abortar el resto de la importación masiva.

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

#### Scenario: Un documento adjunto falla al descargarse durante la importación masiva
- **WHEN** el conector Playwright no logra descargar uno de los documentos adjuntos de un caso durante la importación masiva (por ejemplo, la petición de descarga devuelve un error HTTP)
- **THEN** el conector Playwright registra ese fallo puntual como un paso en estado `error`, omite ese documento del payload del caso, y continúa con el resto de los documentos de ese caso y con los siguientes casos del lote
- **AND** el `trabajo_integracion` de la importación masiva termina en estado `completado`, incluyendo el caso con el documento faltante
- **AND** ningún mensaje de error de descarga expone el `access_token` (u otro parámetro de credencial de un solo uso) de la URL del documento en texto plano

#### Scenario: Un caso individual falla al procesarse durante la importación masiva
- **WHEN** el conector Playwright no logra procesar un caso completo durante la importación masiva (por una falla ajena a un documento puntual, por ejemplo no poder abrir el panel de documentos de esa fila)
- **THEN** el conector Playwright registra ese fallo como un paso en estado `error` y continúa con los siguientes casos del lote, sin abortar la importación masiva completa

#### Scenario: El conector Playwright falla a mitad de la importación masiva
- **WHEN** el conector Playwright falla de forma catastrófica (por ejemplo, se pierde la sesión autenticada o el navegador se cierra inesperadamente) antes de completar la respuesta de la importación masiva
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

### Requirement: Capturar el número de traspaso de la Bandeja SGF
El conector Playwright de SGF SHALL capturar el valor de la columna "N° traspaso" de la Bandeja de procesos y exponerlo en el payload crudo de cada fila, para que el importer pueda conservarlo como referencia del caso. La captura SHALL identificar la columna por su encabezado de texto normalizado (independiente de su posición), reutilizando el mecanismo de mapeo de columnas ya existente.

#### Scenario: La fila de la Bandeja incluye número de traspaso
- **WHEN** el conector Playwright lee una fila de la Bandeja de SGF cuya columna "N° traspaso" tiene un valor
- **THEN** el payload crudo de esa fila incluye ese valor bajo la clave del número de traspaso

#### Scenario: La fila de la Bandeja no tiene número de traspaso
- **WHEN** el conector Playwright lee una fila cuya columna "N° traspaso" está vacía o ausente
- **THEN** el payload crudo de esa fila se genera sin fallar, dejando el número de traspaso vacío o ausente

### Requirement: Nombres de archivo de documentos SGF con codificación correcta
El sistema SHALL reparar el mojibake (UTF-8 interpretado como Latin-1) en los nombres de archivo de los documentos descargados desde SGF antes de usarlos como nombre en disco, `ruta_archivo`, `nombre_archivo` y título del documento. La reparación SHALL aplicarse solo cuando el nombre contenga secuencias de mojibake y su re-decodificación produzca UTF-8 válido y no contenga bytes ya degradados (secuencias `Ã¿` o `Â¿`, cuyo carácter original no es recuperable); en cualquier otro caso el nombre SHALL conservarse intacto. El payload crudo del snapshot SGF SHALL permanecer sin modificar.

#### Scenario: Nombre con mojibake en la tabla de documentos de SGF
- **WHEN** el conector descarga un documento cuyo nombre llega como `CT NÂ°957_433 CONST.EHG.pdf`
- **THEN** el archivo se guarda como `CT N°957_433 CONST.EHG.pdf`
- **AND** el título, `nombre_archivo` y `ruta_archivo` del documento registrado usan ese nombre reparado

#### Scenario: Nombre legítimo sin mojibake
- **WHEN** el conector descarga un documento cuyo nombre no contiene secuencias de mojibake (por ejemplo `Garantía.pdf`)
- **THEN** el nombre se conserva exactamente igual

#### Scenario: Nombre con bytes ya degradados
- **WHEN** el conector descarga un documento cuyo nombre contiene `Ã¿` (por ejemplo `PASAJES_AÃ¿REOS.pdf`, donde el carácter original ya llegó degradado)
- **THEN** el nombre se conserva intacto en lugar de reemplazarlo por un carácter equivocado
- **AND** el comando de reparación lo reporta como irreparable automáticamente para revisión humana

#### Scenario: El payload crudo del snapshot no se altera
- **WHEN** el backend repara el nombre de un documento al registrarlo
- **THEN** el payload crudo del snapshot conserva el nombre tal como lo entregó el conector

### Requirement: Reimportación idempotente con nombres reparados
La reimportación de un caso SGF SHALL NOT duplicar un documento ya registrado, aunque el registro previo tenga la ruta con el nombre corrupto y la nueva importación entregue el nombre reparado (o viceversa).

#### Scenario: Registro previo con nombre corrupto y reimportación con nombre reparado
- **WHEN** un caso ya tiene un documento vinculado con ruta corrupta y se reimporta entregando la misma ruta reparada
- **THEN** no se crea un documento nuevo ni un vínculo adicional

### Requirement: Reparación de nombres de documentos SGF ya importados
El sistema SHALL ofrecer un comando Artisan idempotente que repare los nombres corruptos de documentos SGF ya importados, actualizando `titulo`, `nombre_archivo` y `ruta_archivo` y renombrando el archivo físico, sin modificar vínculos, hashes ni snapshots. El comando SHALL ofrecer modo `--dry-run`, SHALL NOT sobrescribir un archivo existente en el destino, y SHALL registrar auditoría de cada cambio aplicado.

#### Scenario: Ejecución en modo simulación
- **WHEN** se ejecuta el comando con `--dry-run`
- **THEN** lista los documentos que se repararían y no modifica base de datos ni archivos

#### Scenario: Reparación de un documento existente
- **WHEN** se ejecuta el comando sobre un documento con nombre corrupto cuyo archivo físico existe
- **THEN** el título, `nombre_archivo` y `ruta_archivo` quedan reparados, el archivo se renombra y se registra auditoría
- **AND** los vínculos del documento y su hash no cambian

#### Scenario: Conflicto de nombre en el destino
- **WHEN** el archivo con el nombre reparado ya existe en disco
- **THEN** el comando omite ese documento, lo reporta como conflicto y no sobrescribe nada

#### Scenario: Ejecución repetida
- **WHEN** el comando se ejecuta de nuevo tras una reparación exitosa
- **THEN** no encuentra nada que reparar y no modifica datos

