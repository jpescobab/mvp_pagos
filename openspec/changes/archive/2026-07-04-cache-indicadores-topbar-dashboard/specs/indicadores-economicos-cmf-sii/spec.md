## MODIFIED Requirements

### Requirement: Seleccionar indicador para cálculos
El sistema SHALL seleccionar indicadores por fecha (UF, USD) o por periodo (UTM, UTA, IPC), aplicando una regla de fallback parametrizada cuando no exista un valor exacto. La selección del último valor registrado por tipo (usada para mostrar chips de indicadores en topbar y dashboard) SHALL servirse desde caché con invalidación automática cuando se registre un nuevo valor de ese tipo.

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

#### Scenario: Servir el último valor por tipo desde caché
- **WHEN** se solicita el último valor registrado de uno o más tipos (`ultimosPorTipo`) y ya existe una entrada de caché vigente para esos tipos
- **THEN** el sistema retorna el valor cacheado sin consultar `indicadores_economicos`

#### Scenario: Resolver en una sola consulta los tipos sin caché vigente
- **WHEN** se solicita el último valor registrado de varios tipos y ninguno tiene una entrada de caché vigente
- **THEN** el sistema resuelve todos esos tipos con una sola consulta a `indicadores_economicos`, en vez de una consulta independiente por tipo

#### Scenario: Invalidar la caché tras una nueva importación
- **WHEN** `IndicadorEconomicoImporter` registra un nuevo valor para un tipo determinado
- **THEN** el sistema invalida la entrada de caché del último valor de ese tipo
- **AND** la siguiente solicitud de `ultimosPorTipo` para ese tipo refleja el valor recién importado, sin esperar a que expire el TTL
