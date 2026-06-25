# Tarea 01 — Core institucional CAPJ

Implementar tablas y modelos para:

- instituciones
- jurisdicciones
- cfinancieros
- ccostos

Criterios:

- `id` interno como PK.
- código institucional como `unique`.
- `jurisdicciones.codigo` default `14`.
- Relaciones: CAPJ -> jurisdicciones -> cfinancieros -> ccostos.
- Seeds mínimos para CAPJ y jurisdicción inicial.
- Tests de relaciones jerárquicas.
