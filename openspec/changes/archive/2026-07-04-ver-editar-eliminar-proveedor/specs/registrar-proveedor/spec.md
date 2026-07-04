## ADDED Requirements

### Requirement: Ver el detalle de un proveedor registrado
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` ver el detalle completo de un `proveedor` registrado, incluyendo identificación tributaria, clasificación, contacto comercial, domicilio, datos bancarios y notas internas. El sistema SHALL indicar si el proveedor tiene un documento de respaldo bancario adjunto, sin exponer un enlace de descarga directa.

#### Scenario: Ver el detalle completo
- **WHEN** un usuario con permiso `core_institucional.administrar` abre el detalle de un proveedor registrado
- **THEN** la vista muestra todos sus campos, incluyendo los que quedaron en `null` por no haberse completado en el alta

#### Scenario: Sin permiso para ver el detalle
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta abrir el detalle de un proveedor
- **THEN** el sistema responde con un error de autorización

### Requirement: Editar un proveedor existente
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` editar cualquier campo de un proveedor ya registrado, mediante el mismo formulario por pasos usado en el alta, precargado con los datos actuales. El sistema SHALL rechazar la edición si el nuevo RUT coincide con el de otro proveedor distinto del que se está editando. Si se reemplaza el documento de respaldo bancario, el sistema SHALL descartar el archivo anterior.

#### Scenario: Edición exitosa
- **WHEN** un usuario con permiso `core_institucional.administrar` envía el formulario de edición con cambios válidos
- **THEN** el proveedor queda actualizado con los nuevos valores

#### Scenario: RUT en conflicto con otro proveedor
- **WHEN** un usuario edita un proveedor y cambia su RUT a uno que ya pertenece a otro proveedor distinto
- **THEN** el sistema rechaza la operación con un error de validación en el campo RUT y no modifica ningún registro

#### Scenario: Reemplazo del documento de respaldo
- **WHEN** un usuario adjunta un nuevo documento de respaldo bancario al editar un proveedor que ya tenía uno
- **THEN** el sistema guarda el nuevo documento y descarta el anterior

#### Scenario: Sin permiso para editar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta acceder al formulario de edición o enviarlo
- **THEN** el sistema responde con un error de autorización y no modifica el proveedor

### Requirement: Eliminar un proveedor sin relaciones activas
El sistema SHALL permitir a un usuario con el permiso `core_institucional.administrar` eliminar (soft delete) un proveedor que no tenga clientes medidores, casos de pago, facturas ni procesos de adquisición asociados. El sistema SHALL rechazar la eliminación de un proveedor que sí tenga alguna de esas relaciones, indicando cuál lo impide.

#### Scenario: Eliminación exitosa sin relaciones
- **WHEN** un usuario con permiso `core_institucional.administrar` elimina un proveedor que no tiene clientes medidores, casos de pago, facturas ni procesos de adquisición asociados
- **THEN** el proveedor queda eliminado (soft delete) y deja de aparecer en el catálogo

#### Scenario: Eliminación rechazada por relaciones activas
- **WHEN** un usuario intenta eliminar un proveedor que tiene al menos un cliente medidor, caso de pago, factura o proceso de adquisición asociado
- **THEN** el sistema rechaza la eliminación e indica qué relación la impide, sin eliminar el proveedor

#### Scenario: Sin permiso para eliminar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta eliminar un proveedor
- **THEN** el sistema responde con un error de autorización y no elimina el proveedor
