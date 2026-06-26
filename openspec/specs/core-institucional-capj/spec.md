# Spec: core-institucional-capj

## Purpose

Modela la jerarquía institucional fija `instituciones -> jurisdicciones -> cfinancieros -> ccostos` que gobierna permisos, filtros, reportes y trazabilidad del resto del sistema. Es la base de la que dependen por clave foránea casi todos los demás dominios.

## Requirements

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

### Requirement: Sembrar la lista nacional de jurisdicciones
El seeder institucional SHALL poblar las 20 jurisdicciones reales del Poder Judicial (códigos `00` a `18` y `99`) bajo la institución CAPJ, sin sobrescribir el nombre de jurisdicciones ya sembradas previamente.

#### Scenario: Sembrar las jurisdicciones nacionales
- **WHEN** se ejecuta el seeder institucional
- **THEN** existen las 20 jurisdicciones reales, cada una asociada a la institución CAPJ

#### Scenario: Preservar el nombre de una jurisdicción ya sembrada
- **WHEN** el seeder se ejecuta y la jurisdicción `14` ya existe con un nombre distinto al de la lista de origen
- **THEN** el seeder no sobrescribe el nombre existente

### Requirement: Sembrar centros financieros y centros de costo reales
El seeder institucional SHALL poblar los centros financieros y centros de costo reales de la jurisdicción inicial (`14`, Zonal Coyhaique), resolviendo cada centro de costo a su centro financiero por `codigo` (no por id literal).

#### Scenario: Sembrar centros financieros de la jurisdicción inicial
- **WHEN** se ejecuta el seeder institucional
- **THEN** existen los 6 centros financieros reales de la jurisdicción `14` (códigos `1400`, `1401`, `1402`, `1431`, `1451`, `1471`)

#### Scenario: Sembrar centros de costo asociados a su centro financiero correcto
- **WHEN** se ejecuta el seeder institucional
- **THEN** existen los 31 centros de costo reales
- **AND** cada centro de costo pertenece al centro financiero correspondiente a su `codigo` de origen

### Requirement: Asociar opcionalmente un código de edificio a un centro de costo
La tabla `ccostos` SHALL permitir registrar un `cod_edificio` opcional para los centros de costo que correspondan a un inmueble físico concreto.

#### Scenario: Registrar un centro de costo sin código de edificio
- **WHEN** se registra un centro de costo sin especificar `cod_edificio`
- **THEN** el registro se guarda correctamente con `cod_edificio` en `null`

#### Scenario: Registrar un centro de costo con código de edificio
- **WHEN** se registra un centro de costo especificando un `cod_edificio`
- **THEN** el registro guarda ese valor asociado al centro de costo
