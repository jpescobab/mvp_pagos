# Spec: tablas-maestras-institucionales

## Purpose

Modela las tablas maestras institucionales (proveedores, funcionarios, clientes medidores) y el clasificador presupuestario institucional (items, asignaciones, catálogos) sobre las que se apoyan los módulos funcionales — en particular Pago de Proveedores y Consumo Eléctrico.

## Requirements

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

### Requirement: Modelar el clasificador presupuestario institucional
El sistema SHALL modelar `items`, `asignaciones` y `catalogos` como el clasificador presupuestario institucional, donde `asignaciones` y `catalogos` pertenecen directamente a un `item`. El sistema SHALL permitir crear, editar y eliminar asignaciones y catálogos desde el detalle de su ítem padre, sin exponer un listado independiente de todas las asignaciones o catálogos del sistema.

#### Scenario: Registrar una asignación bajo su ítem
- **WHEN** se registra una asignación presupuestaria
- **THEN** queda asociada a su ítem mediante `item_id`
- **AND** su `codigo` es único

#### Scenario: Registrar un catálogo bajo su ítem
- **WHEN** se registra un catálogo (cuenta presupuestaria utilizable)
- **THEN** queda asociado a su ítem mediante `item_id`
- **AND** su `codigo` es único
- **AND** su disponibilidad para uso se controla con el campo `activo`

#### Scenario: Administrar asignaciones y catálogos desde el detalle del ítem
- **WHEN** un usuario con permiso `core_institucional.administrar` visita el detalle de un ítem presupuestario
- **THEN** el sistema muestra sus asignaciones y catálogos asociados, con acciones para crear, editar y eliminar cada uno
- **AND** no existe un listado independiente que muestre asignaciones o catálogos de todos los ítems a la vez

#### Scenario: Editar una asignación o catálogo
- **WHEN** un usuario con permiso `core_institucional.administrar` edita una asignación o catálogo existente
- **THEN** el sistema actualiza `codigo`, `nombre`, `descripcion` y `activo`
- **AND** rechaza el cambio si el nuevo `codigo` colisiona con el de otra asignación o catálogo

#### Scenario: Eliminar una asignación o catálogo
- **WHEN** un usuario con permiso `core_institucional.administrar` elimina una asignación o catálogo
- **THEN** el sistema lo elimina (soft delete)

#### Scenario: Usuario sin permiso no puede administrar asignaciones ni catálogos
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta crear, editar o eliminar una asignación o catálogo
- **THEN** el sistema rechaza la acción

### Requirement: Restringir el listado de tablas maestras al mismo permiso de administración
El sistema SHALL exigir el permiso `core_institucional.administrar` para listar (no solo para ver el detalle, crear, editar o eliminar) proveedores, ítems presupuestarios y clientes medidores.

#### Scenario: Listar con permiso
- **WHEN** un usuario con el permiso `core_institucional.administrar` visita el listado de proveedores, ítems presupuestarios o clientes medidores
- **THEN** el sistema muestra el listado correspondiente

#### Scenario: Usuario sin permiso no puede listar
- **WHEN** un usuario autenticado sin el permiso `core_institucional.administrar` intenta visitar el listado de proveedores, ítems presupuestarios o clientes medidores
- **THEN** el sistema rechaza la solicitud
