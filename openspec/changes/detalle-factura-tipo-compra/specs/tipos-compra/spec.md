## ADDED Requirements

### Requirement: Catálogo administrable de tipos de compra
El sistema SHALL mantener un catálogo `TipoCompra` (código, nombre, activo) que un usuario con el permiso `pago_proveedores.administrar_tipos_compra` pueda crear, editar y desactivar desde la interfaz, sin requerir cambios de código.

#### Scenario: Crear un tipo de compra
- **WHEN** un usuario con el permiso requerido crea un `TipoCompra` con código y nombre
- **THEN** el sistema lo agrega al catálogo, disponible para seleccionarlo al registrar un `DetalleFactura`

#### Scenario: Editar un tipo de compra
- **WHEN** un usuario con el permiso requerido edita el nombre o el estado activo de un `TipoCompra` existente
- **THEN** el sistema guarda los cambios

#### Scenario: No se puede eliminar un tipo de compra en uso
- **WHEN** un usuario intenta eliminar un `TipoCompra` que tiene al menos un `DetalleFactura` asociado
- **THEN** el sistema rechaza la eliminación

### Requirement: Catálogo sembrado con una base genérica de tipos de compra
El sistema SHALL sembrar el catálogo `TipoCompra` con los valores iniciales `BIENES`, `SERVICIOS_GENERALES` y `OBRAS` al desplegarse, de forma idempotente.

#### Scenario: Catálogo disponible desde el primer uso
- **WHEN** se consulta el catálogo `TipoCompra` en un ambiente recién sembrado
- **THEN** el sistema devuelve al menos los tipos `BIENES`, `SERVICIOS_GENERALES` y `OBRAS`
