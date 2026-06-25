# Spec: reportabilidad-informes-razonados

## Requirement: Generar informes razonados desde cortes

El sistema debe generar informes de gestión desde cortes y snapshots.

### Scenario: Generar informe mensual

Given existe un corte mensual publicado
When un usuario autorizado genera el informe
Then el sistema calcula métricas
And detecta excepciones
And genera gráficos
And genera texto razonado en borrador
And conserva snapshot de datos usados
And deja el informe en estado `borrador_generado`

## Requirement: Publicar solo con aprobación humana

Todo informe generado automáticamente requiere revisión y aprobación.

### Scenario: Aprobar informe

Given existe un informe en revisión
When una jefatura autorizada aprueba
Then el sistema registra aprobador y fecha
And cambia estado a `publicado`
And permite exportar Word, PDF, Excel o HTML
