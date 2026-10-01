## ADDED Requirements

### Requirement: Registrar el detalle de una Factura existente
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.registrar_detalle_factura`, registrar un `DetalleFactura` (tipo de compra, centro de costo, cantidad, unidad de medida, monto) para una `Factura` existente. Una `Factura` SHALL tener a lo sumo un `DetalleFactura` asociado.

#### Scenario: Registro exitoso
- **WHEN** un usuario con el permiso requerido registra el detalle de una `Factura` que todavía no tiene ninguno, indicando tipo de compra, centro de costo, cantidad y monto
- **THEN** el sistema crea el `DetalleFactura` vinculado a esa `Factura`

#### Scenario: Falta un campo obligatorio
- **WHEN** un usuario intenta registrar un `DetalleFactura` sin `tipo_compra_id`, `ccosto_id`, `cantidad` o `monto`
- **THEN** el sistema rechaza el registro con un mensaje de validación, sin crear ningún registro

#### Scenario: La Factura ya tiene un detalle registrado
- **WHEN** un usuario intenta registrar un `DetalleFactura` para una `Factura` que ya tiene uno
- **THEN** el sistema rechaza la operación, sin crear un segundo registro

### Requirement: El detalle de una Factura es opcional y no gobierna el pago
El sistema SHALL tratar el registro de un `DetalleFactura` como puramente informativo respecto del `CasoPagoProveedor` al que pertenece su `Factura`: una `Factura` sin `DetalleFactura` SHALL seguir siendo válida para todos los efectos del flujo de pago, y registrar o editar un `DetalleFactura` SHALL NOT inferir, cambiar ni disparar ninguna transición del `Proceso`/workflow del caso.

#### Scenario: Pagar un caso con facturas sin detalle
- **WHEN** un `CasoPagoProveedor` tiene una o más `Factura` sin `DetalleFactura` registrado
- **THEN** el caso puede avanzar y pagarse por el flujo normal, sin que la ausencia del detalle lo bloquee

#### Scenario: Registrar un detalle no altera el estado del caso de pago
- **WHEN** un usuario registra o edita un `DetalleFactura` de una `Factura` perteneciente a un `CasoPagoProveedor`
- **THEN** el estado del `Proceso`/workflow de ese caso no cambia como consecuencia de esa acción

### Requirement: Editar el detalle de una Factura
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.registrar_detalle_factura`, editar los campos de un `DetalleFactura` ya registrado.

#### Scenario: Edición exitosa
- **WHEN** un usuario con el permiso requerido actualiza el tipo de compra, centro de costo, cantidad, unidad de medida o monto de un `DetalleFactura` existente
- **THEN** el sistema guarda los cambios sobre el mismo registro

### Requirement: Permisos del módulo de detalle de factura
El sistema SHALL exponer el permiso `pago_proveedores.registrar_detalle_factura`, en convención `modulo_accion.verbo`, y SHALL condicionar toda acción de creación y edición de `DetalleFactura` a que el usuario autenticado posea ese permiso.

#### Scenario: Usuario sin permiso de registro
- **WHEN** un usuario sin el permiso `pago_proveedores.registrar_detalle_factura` intenta registrar o editar un `DetalleFactura`
- **THEN** el sistema rechaza la acción y registra el evento en `security_audit_logs`
