## Why

Las tareas 5-7 construyeron la infraestructura genérica (workflow-core, expediente documental, evidencia SGF) sin ningún módulo funcional real encima. Tarea 8 es el módulo piloto que cierra el ciclo completo: consume los snapshots SGF (tarea 7) para crear un caso de pago gobernado con su propio proceso de workflow (tarea 5), y deja evidencia de CGU/BancoEstado/egreso sin replicar la lógica de esos sistemas oficiales.

## What Changes

- Crear `casos_pago_proveedor`: un registro por `sgf_id`, con su `proceso` (1:1) y datos de referencia SGF (`sgf_status`, `sgf_current_group_raw`) guardados solo como evidencia, nunca como gobierno.
- Crear `App\Services\PagoProveedores\CasoPagoProveedorImporter::importarDesdeSnapshot(SnapshotSgf $snapshot): CasoPagoProveedor` — si no existe un caso para ese `sgf_id`, lo crea junto con su `Proceso` en el estado inicial (`importada_desde_sgf`); si ya existe, solo actualiza los campos de referencia SGF sin tocar el workflow interno.
- Sembrar la `DefinicionWorkflow` "pago_proveedores" con los 13 estados sugeridos por `HARNESS_IA.md` (sección 10) y transiciones razonables entre ellos.
- Crear `facturas`: datos estructurados de la factura del caso (folio, monto, fecha de emisión, proveedor), distinto del archivo PDF que ya vive en `documentos` (tarea 6).
- Crear `registros_contables_cgu` y `registros_pago_bancario`: evidencia registrada manualmente (sin API real a CGU/BancoEstado todavía — mismo criterio que SGF en tarea 7) de que un caso fue registrado en CGU o pagado vía BancoEstado.
- Crear `egresos_cgu` y `egresos_cgu_items`: un egreso CGU puede cubrir varios casos a la vez (relación muchos-a-muchos vía `egresos_cgu_items`), con respaldo documental opcional.

## Capabilities

### New Capabilities
- `pago-proveedores-sgf`: primer módulo funcional activable. Un `sgf_id` = un `caso_pago_proveedor` = un proceso de workflow individual. No se modelan lotes ni envíos iniciales (`payment_submissions`). CGU, BancoEstado y egreso CGU se registran como evidencia, no se reemplaza su lógica oficial.

### Modified Capabilities
(ninguna — no cambia comportamiento de `workflow-core`, `documentos-expediente-variable` ni `sgf-origen-snapshot`; solo las consume.)

## Impact

- Migraciones nuevas: `casos_pago_proveedor`, `facturas`, `registros_contables_cgu`, `registros_pago_bancario`, `egresos_cgu`, `egresos_cgu_items`.
- Código nuevo: `App\Models\CasoPagoProveedor`, `Factura`, `RegistroContableCgu`, `RegistroPagoBancario`, `EgresoCgu`, `EgresoCguItem`; `App\Services\PagoProveedores\CasoPagoProveedorImporter`.
- Nuevo seeder: `WorkflowPagoProveedoresSeeder` (definición + 13 estados + transiciones).
- No se construye UI ni endpoints HTTP en esta tarea (no estaba en alcance de ninguna tarea anterior tampoco); es la capa de dominio/servicio.
