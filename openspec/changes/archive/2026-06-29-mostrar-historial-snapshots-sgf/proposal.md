## Why

`SnapshotSgf` conserva, desde la tarea 7, la evidencia inmutable de cada fila SGF importada (`payload_crudo`, `payload_normalizado`, `hash`, `capturado_en`, su `ImportacionSgf` de origen), append-only por diseño (un mismo `sgf_id` puede tener varios snapshots a lo largo del tiempo). Sin embargo, ningún controlador ni página expone jamás ese historial: el detalle de un `caso_pago_proveedor` solo muestra `sgf_status`/`sgf_current_group_raw` como referencia vigente, pero no la evidencia que los originó. Esto contradice el principio de "snapshot obligatorio" del harness, cuyo propósito explícito es servir de evidencia, auditoría, conciliación y trazabilidad — no solo conservarse en la base de datos sin consulta posible.

## What Changes

- Exponer el historial completo de `snapshots_sgf` (no solo el más reciente) en el detalle de un `caso_pago_proveedor`, ordenado del más reciente al más antiguo.
- Cada snapshot muestra su fecha de captura, hash, fuente de la importación (`importacion_sgf.fuente`) y permite ver el `payload_crudo`/`payload_normalizado` completo (expandible), igual que el detalle de auditoría ya construido para `audit_logs`.
- Es de solo lectura: no se agrega ninguna acción de escritura ni permiso nuevo (ya gatea `Gate::authorize('view', $caso)`, el mismo permiso con el que hoy se ve el resto del detalle).

## Capabilities

### Modified Capabilities
- `sgf-origen-snapshot`: gana un Requirement nuevo — el historial de `snapshots_sgf` de un `sgf_id` deja de ser invisible y se expone en el detalle de su `caso_pago_proveedor` gobernado.

## Impact

- Nuevo: relación `CasoPagoProveedor::snapshotsSgf()` (`hasMany` por `sgf_id`, sin FK real — el emparejamiento ya es por valor de `sgf_id`, mismo criterio que usa `CasoPagoProveedorImporter`).
- Modificados: `CasoPagoProveedorController::show()` (eager load), `CasoPagoProveedorResource`, `resources/js/pages/pago-proveedores/casos/show.tsx`, `resources/js/types/pago-proveedores.ts`.
- Sin cambios de esquema ni de permisos.
