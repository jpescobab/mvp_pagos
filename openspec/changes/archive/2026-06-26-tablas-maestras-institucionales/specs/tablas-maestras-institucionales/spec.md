## ADDED Requirements

### Requirement: Incorporar tablas maestras institucionales desde el inicio
El sistema SHALL incorporar desde el inicio `proveedores`, `funcionarios` y `clientes_medidores`, cada una con `id` interno y un identificador institucional único.

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

### Requirement: Modelar el clasificador presupuestario institucional
El sistema SHALL modelar `items`, `asignaciones` y `catalogos` como el clasificador presupuestario institucional, donde `asignaciones` y `catalogos` pertenecen directamente a un `item`.

#### Scenario: Registrar una asignación bajo su ítem
- **WHEN** se registra una asignación presupuestaria
- **THEN** queda asociada a su ítem mediante `item_id`
- **AND** su `codigo` es único

#### Scenario: Registrar un catálogo bajo su ítem
- **WHEN** se registra un catálogo (cuenta presupuestaria utilizable)
- **THEN** queda asociado a su ítem mediante `item_id`
- **AND** su `codigo` es único
- **AND** su disponibilidad para uso se controla con el campo `activo`
