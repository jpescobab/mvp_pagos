## ADDED Requirements

### Requirement: Modelar jerarquía institucional CAPJ
El sistema SHALL modelar la jerarquía `instituciones -> jurisdicciones -> cfinancieros -> ccostos` mediante tablas con `id` interno como PK y relaciones de clave foránea encadenadas hacia el nivel superior.

#### Scenario: Crear estructura inicial CAPJ
- **WHEN** se ejecutan las migraciones y el seeder inicial por primera vez
- **THEN** existe una institución CAPJ activa
- **AND** existe una jurisdicción inicial con código por defecto `14`

#### Scenario: Registrar un centro de costo trazable hasta CAPJ
- **WHEN** se registra un centro de costo asociado a un centro financiero, que a su vez pertenece a una jurisdicción de la institución CAPJ
- **THEN** el centro de costo es trazable hasta la institución CAPJ siguiendo la cadena `ccosto -> cfinanciero -> jurisdiccion -> institucion`

### Requirement: Mantener códigos institucionales únicos
Las tablas `instituciones`, `jurisdicciones`, `cfinancieros` y `ccostos` SHALL usar `id` interno como PK y exigir que su `codigo` institucional sea único a nivel de base de datos.

#### Scenario: Código institucional duplicado es rechazado
- **WHEN** se intenta registrar un segundo registro con el mismo `codigo` en cualquiera de las cuatro tablas de la jerarquía
- **THEN** la base de datos rechaza la operación por violación de la restricción `unique`

#### Scenario: No se puede eliminar un nivel de la jerarquía con hijos asociados
- **WHEN** se intenta eliminar una institución, jurisdicción o centro financiero que todavía tiene registros hijos asociados
- **THEN** la operación se rechaza para no romper la trazabilidad de la jerarquía
