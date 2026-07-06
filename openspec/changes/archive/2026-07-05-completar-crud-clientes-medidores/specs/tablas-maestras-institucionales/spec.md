## MODIFIED Requirements

### Requirement: Incorporar tablas maestras institucionales desde el inicio
El sistema SHALL incorporar desde el inicio `proveedores`, `funcionarios` y `clientes_medidores`, cada una con `id` interno y un identificador institucional único. El sistema SHALL permitir editar y eliminar clientes medidores, además de crearlos, con el mismo permiso `core_institucional.administrar` que gobierna el resto de tablas maestras.

#### Scenario: Crear maestro de proveedores
- **WHEN** se registra un proveedor
- **THEN** el sistema asigna `id` interno
- **AND** conserva `rutproveedor` como identificador institucional único
- **AND** permite asociarlo a casos de pago, documentos y reportes

#### Scenario: Crear funcionario
- **WHEN** se registra un funcionario
- **THEN** el sistema conserva `rut` único
- **AND** permite relacionarlo opcionalmente con `users`
- **AND** lo asocia a centro de costo y/o centro financiero cuando corresponda

#### Scenario: Registrar cliente medidor
- **WHEN** se registra un cliente medidor
- **THEN** queda asociado opcionalmente a un proveedor de servicio
- **AND** queda asociado a un centro de costo (`ccosto`) de la jerarquía CAPJ
- **AND** puede usarse en consumo eléctrico, reportes y trazabilidad

#### Scenario: Editar un cliente medidor
- **WHEN** un usuario con permiso `core_institucional.administrar` edita un cliente medidor existente
- **THEN** el sistema actualiza `numero_cliente`, `proveedor_id`, `ccosto_id`, `tipo_suministro`, `direccion_suministro` y `activo`
- **AND** rechaza el cambio si el nuevo `numero_cliente` colisiona con el de otro cliente medidor

#### Scenario: Eliminar un cliente medidor
- **WHEN** un usuario con permiso `core_institucional.administrar` elimina un cliente medidor
- **THEN** el sistema lo elimina (soft delete)

#### Scenario: Usuario sin permiso no puede administrar clientes medidores
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta crear, editar o eliminar un cliente medidor
- **THEN** el sistema rechaza la acción
