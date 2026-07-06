# Spec: indicadores-economicos-cmf-sii

## Purpose

Importa y conserva con trazabilidad los indicadores económicos oficiales (UF, USD, UTM, UTA, IPC) usados para cálculos, reportes, cortes e informes razonados.

## Requirements

### Requirement: Importar indicadores económicos oficiales
El sistema SHALL importar y almacenar indicadores económicos desde la API oficial de la CMF (UF, USD, UTM, IPC) y calcular UTA a partir de UTM cuando corresponda, conservando snapshot de origen en cada importación y en cada valor. Cada indicador SHALL guardarse con un `codigo` (UF/USD/UTM/UTA/IPC) distinto de su `tipo` (categoría semántica: `unidad_reajustable`, `unidad_tributaria`, `moneda`, `indice`), y SHALL identificarse de forma única por la combinación `codigo + fecha_valor + periodo + fuente + es_proyectado`. El sistema NUNCA SHALL sobrescribir un indicador ya existente; si la combinación ya existe, se omite y se registra como omitido en la importación correspondiente.

#### Scenario: Importación mensual día 10
- **WHEN** se ejecuta el job mensual de indicadores económicos
- **THEN** el sistema consulta la API de la CMF
- **AND** importa UF (tramo mensual vigente), UTM e IPC, y calcula UTA si la UTM de diciembre del año comercial vigente está disponible
- **AND** guarda cada valor normalizado en `indicadores_economicos` con su `codigo`, `tipo` y clave única completa
- **AND** registra la ejecución en `indicadores_economicos_importaciones`
- **AND** conserva `source_payload`, endpoint, fuente, hash, advertencias y errores

#### Scenario: UF con valores diarios por tramo mensual
- **WHEN** el sistema procesa la respuesta de la CMF para el tramo mensual vigente (día 10 de un mes a día 9 del mes siguiente)
- **THEN** guarda un registro por cada fecha de valor dentro del tramo
- **AND** asigna `periodicidad_valor = diaria` y `periodicidad_publicacion = tramo_mensual`
- **AND** registra `vigente_desde` y `vigente_hasta` con los límites del tramo

#### Scenario: Determinar el tramo vigente de UF según la fecha de ejecución, no el mes calendario
- **WHEN** la importación de UF se ejecuta en cualquier día del mes (programada, manual o de reproceso), no solo el día 10
- **THEN** si la fecha de ejecución es anterior al día 10 de su mes, el sistema consulta el tramo que comenzó el día 10 del mes anterior (el del mes en curso todavía no ha sido publicado por la CMF)
- **AND** si la fecha de ejecución es igual o posterior al día 10 de su mes, el sistema consulta el tramo que comienza ese mismo día 10

#### Scenario: USD diario
- **WHEN** se ejecuta el job diario de dólar observado
- **THEN** consulta la API de la CMF para USD
- **AND** guarda el valor para la fecha que la CMF reporte como vigente
- **AND** si la fecha reportada no coincide con la fecha esperada (día inhábil o sin publicación), registra una advertencia en la importación
- **AND** si la CMF no entrega ningún valor, el sistema no crea un valor artificial y registra la advertencia correspondiente en la importación

#### Scenario: UTA calculada desde UTM
- **WHEN** la UTM de diciembre del año comercial vigente ya está disponible
- **THEN** el sistema calcula UTA como `UTM(diciembre) × 12`
- **AND** guarda el valor con `fuente = calculado_utm` y referencia a la UTM usada en `source_payload`

#### Scenario: Indicador ya existente se omite sin sobrescribir
- **WHEN** el proceso de importación recibe nuevamente un indicador cuya combinación `codigo + fecha_valor + periodo + fuente + es_proyectado` ya existe
- **THEN** el sistema no crea un nuevo registro ni modifica el existente
- **AND** incrementa el contador `total_omitidos` de la importación en curso

### Requirement: Seleccionar indicador para cálculos
El sistema SHALL seleccionar indicadores por fecha (UF, USD) o por periodo (UTM, UTA, IPC), aplicando una regla de fallback parametrizada cuando no exista un valor exacto. La selección del último valor registrado por `codigo` (usada para mostrar chips de indicadores en topbar y dashboard) SHALL servirse desde caché con invalidación automática cuando se registre un nuevo valor de ese `codigo`.

#### Scenario: Calcular con UF
- **WHEN** un cálculo requiere UF para una fecha específica
- **THEN** el sistema selecciona el registro con `codigo = UF` y `fecha_valor` correspondiente

#### Scenario: Calcular con UTM o UTA
- **WHEN** un cálculo requiere UTM o UTA para un periodo
- **THEN** el sistema selecciona el registro correspondiente por `codigo` y `periodo`

#### Scenario: Calcular con USD sin valor exacto
- **WHEN** un cálculo requiere USD para una fecha sin valor exacto registrado
- **THEN** el sistema aplica la regla de fallback configurada (`config('indicadores.usd_fallback')`)
- **AND** retorna el valor resultante de esa regla en vez de fallar

#### Scenario: Servir el último valor por código desde caché
- **WHEN** se solicita el último valor registrado de uno o más códigos (`ultimosPorTipo`) y ya existe una entrada de caché vigente para esos códigos
- **THEN** el sistema retorna el valor cacheado sin consultar `indicadores_economicos`

#### Scenario: Resolver en una sola consulta los códigos sin caché vigente
- **WHEN** se solicita el último valor registrado de varios códigos y ninguno tiene una entrada de caché vigente
- **THEN** el sistema resuelve todos esos códigos con una sola consulta a `indicadores_economicos`, en vez de una consulta independiente por código

#### Scenario: Invalidar la caché tras una nueva importación
- **WHEN** el servicio de persistencia registra un nuevo valor para un `codigo` determinado
- **THEN** el sistema invalida la entrada de caché del último valor de ese `codigo`
- **AND** la siguiente solicitud de `ultimosPorTipo` para ese código refleja el valor recién importado, sin esperar a que expire el TTL

### Requirement: Registrar el estado de cada ejecución de importación
Toda ejecución de importación (mensual, diaria, manual o de reproceso controlado) SHALL registrarse en `indicadores_economicos_importaciones` con un `tipo_importacion` (`mensual_indicadores`, `diaria_usd`, `manual`, `reproceso_controlado`) y un `estado` que transiciona de `pending` a `running` al iniciar, y finaliza en `success` (todos los indicadores procesados sin error), `partial_success` (al menos uno falló y al menos uno se guardó u omitió correctamente) o `failed` (todos fallaron).

#### Scenario: Ejecución exitosa
- **WHEN** una importación procesa todos sus indicadores solicitados sin errores
- **THEN** la importación finaliza con `estado = success`
- **AND** registra `total_recibidos`, `total_creados`, `total_omitidos` y `total_fallidos`

#### Scenario: Ejecución parcialmente exitosa
- **WHEN** una importación logra procesar (crear u omitir) al menos un indicador pero falla en al menos otro
- **THEN** la importación finaliza con `estado = partial_success`

#### Scenario: Ejecución fallida
- **WHEN** ninguno de los indicadores solicitados pudo procesarse
- **THEN** la importación finaliza con `estado = failed`

### Requirement: Reprocesar un período o fecha de forma controlada
El sistema SHALL permitir reprocesar manualmente un período (`indicadores:importar-mensual --periodo=`) o una fecha (`indicadores:importar-usd --fecha=`) sin sobrescribir valores ya existentes, registrando la ejecución con `tipo_importacion = reproceso_controlado`.

#### Scenario: Reprocesar un período ya importado
- **WHEN** se ejecuta `indicadores:importar-mensual --periodo=2026-07` y ya existen indicadores para ese período
- **THEN** el sistema omite los indicadores ya existentes y solo crea los que falten
- **AND** registra la ejecución con `tipo_importacion = reproceso_controlado`

### Requirement: Disparar la importación mensual bajo demanda con autorización
El sistema SHALL permitir a un usuario con el permiso `indicadores.importar` disparar la importación mensual (UF, UTM, UTA, IPC) desde la interfaz, además de su ejecución programada.

#### Scenario: Usuario autorizado dispara la importación
- **WHEN** un usuario con el permiso `indicadores.importar` solicita ejecutar la importación mensual desde la página de indicadores económicos
- **THEN** el sistema ejecuta la misma lógica que el job programado y registra la ejecución con `tipo_importacion = manual`

#### Scenario: Usuario sin permiso no puede disparar la importación
- **WHEN** un usuario autenticado sin el permiso `indicadores.importar` intenta disparar la importación manual
- **THEN** el sistema rechaza la solicitud
