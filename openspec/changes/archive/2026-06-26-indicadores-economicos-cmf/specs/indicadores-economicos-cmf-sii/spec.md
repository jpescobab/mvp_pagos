## ADDED Requirements

### Requirement: Importar indicadores económicos oficiales
El sistema SHALL importar y almacenar indicadores económicos desde la API oficial de la CMF (UF, USD, UTM, IPC) y calcular UTA a partir de UTM cuando corresponda, conservando snapshot de origen en cada importación y en cada valor.

#### Scenario: Importación mensual día 10
- **WHEN** se ejecuta el job mensual de indicadores económicos
- **THEN** el sistema consulta la API de la CMF
- **AND** importa UF (tramo mensual vigente), UTM e IPC, y calcula UTA si la UTM de diciembre del año comercial vigente está disponible
- **AND** guarda cada valor normalizado en `indicadores_economicos`
- **AND** registra la ejecución en `indicadores_economicos_importaciones`
- **AND** conserva `source_payload`, endpoint, fuente, hash, advertencias y errores

#### Scenario: UF con valores diarios por tramo mensual
- **WHEN** el sistema procesa la respuesta de la CMF para el tramo mensual vigente (día 10 del mes actual a día 9 del mes siguiente)
- **THEN** guarda un registro por cada fecha de valor dentro del tramo
- **AND** asigna `periodicidad_valor = diaria` y `periodicidad_publicacion = tramo_mensual`
- **AND** registra `vigente_desde` y `vigente_hasta` con los límites del tramo

#### Scenario: USD diario
- **WHEN** se ejecuta el job diario de dólar observado
- **THEN** consulta la API de la CMF para USD
- **AND** guarda el valor para la fecha que la CMF reporte como vigente
- **AND** si la fecha reportada no coincide con la fecha esperada (día inhábil o sin publicación), registra una advertencia en la importación

#### Scenario: UTA calculada desde UTM
- **WHEN** la UTM de diciembre del año comercial vigente ya está disponible
- **THEN** el sistema calcula UTA como `UTM(diciembre) × 12`
- **AND** guarda el valor con `fuente = calculado_utm` y referencia a la UTM usada en `source_payload`

### Requirement: Seleccionar indicador para cálculos
El sistema SHALL seleccionar indicadores por fecha (UF, USD) o por periodo (UTM, UTA, IPC), aplicando una regla de fallback parametrizada cuando no exista un valor exacto.

#### Scenario: Calcular con UF
- **WHEN** un cálculo requiere UF para una fecha específica
- **THEN** el sistema selecciona el registro UF con `fecha_valor` correspondiente

#### Scenario: Calcular con UTM o UTA
- **WHEN** un cálculo requiere UTM o UTA para un periodo
- **THEN** el sistema selecciona el registro correspondiente por `periodo`

#### Scenario: Calcular con USD sin valor exacto
- **WHEN** un cálculo requiere USD para una fecha sin valor exacto registrado
- **THEN** el sistema aplica la regla de fallback configurada (`config('indicadores.usd_fallback')`)
- **AND** retorna el valor resultante de esa regla en vez de fallar
