# Spec: indicadores-economicos-cmf-sii

## Requirement: Importar indicadores económicos oficiales

El sistema debe importar y almacenar indicadores económicos desde APIs oficiales, principalmente CMF y cuando corresponda SII u otra fuente configurada.

### Scenario: Importación mensual día 10

Given es día 10 del mes
When se ejecuta el job mensual de indicadores económicos
Then el sistema consulta la fuente configurada
And importa UF, UTM, UTA e IPC
And guarda cada valor normalizado en `indicadores_economicos`
And registra la ejecución en `indicadores_economicos_importaciones`
And conserva `source_payload`, endpoint, fuente, hash, advertencias y errores

### Scenario: UF con valores diarios por tramo

Given la fuente entrega valores UF para un tramo mensual
When el sistema procesa la respuesta
Then guarda un registro por cada fecha de valor
And asigna `periodicidad_valor = diaria`
And asigna `periodicidad_publicacion = tramo_mensual`
And registra `vigente_desde` y `vigente_hasta`

### Scenario: USD diario

Given existe un job diario de dólar observado
When el job se ejecuta
Then consulta la fuente configurada para USD
And guarda el valor del día si está disponible
And si no hay valor por día inhábil o sin publicación registra advertencia
And aplica solo la regla de fallback definida por parámetros

## Requirement: Seleccionar indicador para cálculos

El sistema debe seleccionar indicadores por fecha o periodo según tipo.

### Scenario: Calcular con UF

Given un cálculo requiere UF para una fecha específica
When el sistema busca el indicador
Then selecciona el registro UF con `fecha_valor` correspondiente
And guarda snapshot del valor usado en el cálculo si impacta gestión o reporte

### Scenario: Calcular con UTM

Given un cálculo requiere UTM para un mes
When el sistema busca el indicador
Then selecciona el registro UTM por `periodo`
