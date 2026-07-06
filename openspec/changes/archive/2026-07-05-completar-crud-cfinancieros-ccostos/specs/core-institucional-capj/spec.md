## MODIFIED Requirements

### Requirement: Mantener códigos institucionales únicos
Las tablas `instituciones`, `jurisdicciones`, `cfinancieros` y `ccostos` SHALL usar `id` interno como PK y exigir que su `codigo` institucional sea único a nivel de base de datos. El sistema SHALL exponer un CRUD administrable (HTTP + UI) sobre `cfinancieros` y `ccostos`, gateado por el mismo permiso que el resto de tablas maestras, con verificación explícita a nivel de aplicación de las relaciones dependientes antes de eliminar.

#### Scenario: Código institucional duplicado es rechazado
- **WHEN** se intenta registrar un segundo registro con el mismo `codigo` en cualquiera de las cuatro tablas de la jerarquía
- **THEN** la base de datos rechaza la operación por violación de la restricción `unique`

#### Scenario: No se puede eliminar un nivel de la jerarquía con hijos asociados
- **WHEN** se intenta eliminar una institución, jurisdicción o centro financiero que todavía tiene registros hijos asociados
- **THEN** la operación se rechaza para no romper la trazabilidad de la jerarquía

#### Scenario: No se puede eliminar un centro de costo con registros asociados
- **WHEN** un usuario con permiso `core_institucional.administrar` intenta eliminar un centro de costo que tiene clientes medidores o procesos de adquisición asociados
- **THEN** el sistema rechaza la eliminación y explica el motivo, antes de que la base de datos llegue a rechazarla por la restricción de clave foránea

#### Scenario: Crear, editar y eliminar un centro financiero
- **WHEN** un usuario con permiso `core_institucional.administrar` crea, edita o elimina un centro financiero sin centros de costo asociados
- **THEN** el sistema aplica el cambio, exigiendo `codigo` único y una `jurisdiccion_id` válida

#### Scenario: Crear, editar y eliminar un centro de costo
- **WHEN** un usuario con permiso `core_institucional.administrar` crea, edita o elimina un centro de costo sin registros dependientes
- **THEN** el sistema aplica el cambio, exigiendo `codigo` único y una `cfinanciero_id` válida

#### Scenario: Usuario sin permiso no puede administrar centros financieros ni centros de costo
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta crear, editar o eliminar un centro financiero o un centro de costo
- **THEN** el sistema rechaza la acción
