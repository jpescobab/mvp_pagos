# Spec: core-institucional-capj

## Requirement: Modelar jerarquía institucional CAPJ

El sistema debe modelar la jerarquía CAPJ -> Jurisdicciones -> Centros financieros -> Centros de costos.

### Scenario: Crear estructura inicial CAPJ

Given el sistema se instala por primera vez
When se ejecutan migraciones y seeders iniciales
Then existe una institución CAPJ activa
And existe una jurisdicción inicial con código por defecto `14`
And cada centro financiero pertenece a una jurisdicción
And cada centro de costo pertenece a un centro financiero

## Requirement: Mantener códigos institucionales

Las tablas estructurales deben usar `id` interno y código institucional único.

### Scenario: Registrar centro de costo

Given existe un centro financiero activo
When se registra un centro de costo
Then el sistema guarda `id` interno
And guarda código institucional `ccosto` como único
And permite trazar el centro de costo hasta CAPJ
