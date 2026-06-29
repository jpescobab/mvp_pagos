## Why

La tarea 08 (`pago-proveedores-sgf`) ya define la tabla `facturas` y el modelo `Factura` (`caso_pago_proveedor_id`, `proveedor_id` opcional, `folio`, `monto`, `fecha_emision`) como "dato estructurado distinto de documentos": el documento `FACTURA` guarda el archivo/evidencia, `facturas` guarda el dato consultable (folio, monto, fecha de emisión). Hoy esa tabla nunca se escribe — no hay controlador, service ni seeder que cree un registro — ni se expone: `CasoPagoProveedorController::show()` no la carga y `CasoPagoProveedorResource` no la serializa. La transición de workflow `aprobar_documentacion` exige el tipo documental `FACTURA` como archivo, pero el dato estructurado equivalente queda huérfano sin forma de registrarse ni consultarse, igual que ya se resolvió para los registros contables CGU y los registros de pago bancario.

## What Changes

- Nuevo `FacturaController::store()` para registrar una factura (folio, monto, fecha de emisión) asociada a un caso de pago de proveedor, siguiendo el mismo patrón que `RegistroContableCguController` y `RegistroPagoBancarioController`: Form Request de validación, autorización vía Policy/Gate, transacción DB, registro en `AuditLogger`.
- Nueva ability `registrarFactura` en `CasoPagoProveedorPolicy`, respaldada por un nuevo permiso Spatie `pago_proveedores.registrar_factura`.
- `CasoPagoProveedorController::show()` carga la relación `facturas` y `CasoPagoProveedorResource` la serializa (id, folio, monto, fecha_emision), mismo estilo que `registros_contables_cgu` / `egresos_cgu`.
- Página `resources/js/pages/pago-proveedores/casos/show.tsx`: formulario para registrar factura (folio, monto, fecha de emisión) junto a los formularios existentes de registro CGU/pago bancario, y listado de las facturas ya registradas para el caso.
- Sin cambios de esquema: la tabla y el modelo `Factura` ya existen tal cual desde la tarea 08.

## Capabilities

### New Capabilities
- `registrar-factura-caso-pago-proveedor`: registrar y consultar las facturas (folio, monto, fecha de emisión) asociadas a un caso de pago de proveedor.

### Modified Capabilities
(ninguna — `pago-proveedores-sgf` no documenta a `facturas` como requirement propio; esta capability nueva cubre el comportamiento completo)

## Impact

- Nuevos: `App\Http\Controllers\PagoProveedores\FacturaController`, `App\Http\Requests\PagoProveedores\RegistrarFacturaRequest`, permiso `pago_proveedores.registrar_factura`, ruta de `store` en el archivo de rutas de pago-proveedores.
- Modificados: `App\Policies\CasoPagoProveedorPolicy` (nueva ability `registrarFactura`), `App\Http\Controllers\PagoProveedores\CasoPagoProveedorController` (eager load de `facturas`), `App\Http\Resources\PagoProveedores\CasoPagoProveedorResource` (serialización de `facturas`), `resources/js/pages/pago-proveedores/casos/show.tsx` (formulario + listado), seeder de permisos/roles existente para incluir el nuevo permiso.
- Sin cambios de esquema ni de modelos de datos.
