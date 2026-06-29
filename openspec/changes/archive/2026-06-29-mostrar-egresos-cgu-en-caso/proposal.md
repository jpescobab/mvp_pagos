## Why

El workflow de Pago de Proveedores tiene una transición explícita `asociar_egreso_cgu` (`pagada_bancoestado → asociada_a_egreso_cgu`), y `EgresoCguController` ya permite crear un `egreso_cgu` que cubre uno o más `caso_pago_proveedor` mediante `egresos_cgu_items`. Sin embargo, el detalle de un caso de pago (`pago-proveedores/casos/show`) nunca muestra a qué egreso quedó asociado: hay que ir manualmente al listado de egresos y revisar cada uno para encontrar la referencia. Es un punto ciego de trazabilidad que conecta dos piezas que ya existen — el caso y el detalle de egreso CGU (`egresos-cgu/show`, ya construido) — sin que estén enlazadas entre sí.

## What Changes

- Mostrar, en el detalle de un `caso_pago_proveedor`, los `egreso_cgu` asociados (vía `egresos_cgu_items`), cada uno con su número, fecha y el monto específico que cubre para ese caso.
- Cada egreso mostrado enlaza a su página de detalle ya existente (`pago-proveedores.egresos-cgu.show`).
- Es de solo lectura: no se agrega ninguna acción de escritura ni permiso nuevo (ya gatea `Gate::authorize('view', $caso)`, mismo permiso con el que hoy se ve el resto del detalle).

## Capabilities

### Modified Capabilities
- `pago-proveedores-sgf`: el Requirement "Registrar CGU, BancoEstado y egreso CGU como evidencia" gana un escenario — el egreso CGU que cubre a un caso deja de ser invisible desde el propio detalle del caso.

## Impact

- Nuevo: relación `CasoPagoProveedor::egresoCguItems()` (`hasMany` por `caso_pago_proveedor_id`, FK real ya existente en `egresos_cgu_items`).
- Modificados: `CasoPagoProveedorController::show()` (eager load), `CasoPagoProveedorResource`, `resources/js/pages/pago-proveedores/casos/show.tsx`, `resources/js/types/pago-proveedores.ts`.
- Sin cambios de esquema ni de permisos.
