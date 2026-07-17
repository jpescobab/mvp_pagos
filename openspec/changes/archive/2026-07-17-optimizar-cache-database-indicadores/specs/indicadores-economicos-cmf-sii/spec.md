## MODIFIED Requirements

### Requirement: Seleccionar indicador para cálculos
El sistema SHALL seleccionar indicadores por fecha (UF, USD) o por periodo (UTM, UTA, IPC), aplicando una regla de fallback parametrizada cuando no exista un valor exacto. La selección del último valor registrado por `codigo` (usada para mostrar chips de indicadores en topbar y dashboard) SHALL servirse desde caché con invalidación automática cuando se registre un nuevo valor de ese `codigo`. Dentro de un mismo request, una llamada SHALL reutilizar lo que ya haya resuelto una llamada anterior para los mismos códigos, sin repetir ninguna operación de caché ni de base de datos.

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
- **AND** esa consulta trae únicamente la fila más reciente de cada código, no todas las filas que matcheen

#### Scenario: Invalidar la caché tras una nueva importación
- **WHEN** el servicio de persistencia registra un nuevo valor para un `codigo` determinado
- **THEN** el sistema invalida la entrada de caché del último valor de ese `codigo`
- **AND** la siguiente solicitud de `ultimosPorTipo` para ese código refleja el valor recién importado, sin esperar a que expire el TTL

#### Scenario: Consultar el store de caché una sola vez para varios códigos
- **WHEN** se solicita el último valor registrado de varios códigos sin memo de instancia previo
- **THEN** el sistema consulta el store de caché con una sola operación para todos esos códigos, en vez de una operación independiente por código

#### Scenario: Reutilizar dentro del mismo request lo ya resuelto por otra llamada
- **WHEN** dos llamadas distintas a `ultimosPorTipo` ocurren dentro del mismo request HTTP con conjuntos de códigos parcialmente superpuestos
- **THEN** la segunda llamada no repite ninguna operación de caché ni de base de datos para los códigos que ya fueron resueltos por la primera
- **AND** solo resuelve los códigos que no habían sido pedidos todavía en ese request
