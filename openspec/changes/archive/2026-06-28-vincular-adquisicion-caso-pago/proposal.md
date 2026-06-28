## Why

Hoy un `CasoPagoProveedor` (que nace siempre de un `sgf_id` vía SGF) y un `ProcesoAdquisicion` (que nace del módulo de Adquisiciones) no tienen ningún vínculo de datos entre sí. Cuando SGF finalmente paga un contrato/compra que fue adjudicado en Adquisiciones, no existe forma de saber, desde el sistema, qué `ProcesoAdquisicion` originó ese pago — la correlación hoy depende de que una persona recuerde o compare manualmente proveedor/monto/fecha fuera del sistema. Esto rompe la trazabilidad institucional de punta a punta (Adquisiciones → SGF → Pago de Proveedores) que el harness exige como evidencia.

No existe todavía una integración con Mercado Público que entregue un número de orden de compra estructurado, así que la correlación automática por ese dato no es viable por ahora. Lo que sí es viable y útil hoy es dejar que una persona vincule manualmente, con búsqueda asistida, un caso de pago a la adquisición que lo originó.

## What Changes

- Se agrega una columna nullable `proceso_adquisicion_id` (FK) en `casos_pago_proveedor`, siguiendo el mismo patrón de FK directa (no polimórfica) que ya usa `egresos_cgu_items.caso_pago_proveedor_id`.
- Cardinalidad muchos-a-uno: varios `CasoPagoProveedor` pueden vincularse al mismo `ProcesoAdquisicion` (pagos por avance contra un mismo contrato).
- Nueva acción manual "Vincular a proceso de adquisición" desde la vista de detalle de un Caso de Pago de Proveedores: búsqueda asistida por código (`ADQ-XXXX`), objeto, proveedor o monto sobre `procesos_adquisicion`. No hay matching automático por texto libre de SGF (`observaciones`/`payload_crudo`) por ser poco confiable.
- Esta acción **no** es una transición de workflow (no cambia `estado_actual_id` de ningún `Proceso`), por lo que no pasa por `TransicionWorkflowService::execute()`. Sigue, sin embargo, registrada en auditoría por ser una decisión humana con impacto en trazabilidad financiera.
- Nuevo permiso `pago_proveedores.vincular_adquisicion` que gobierna quién puede crear o quitar el vínculo.
- La vista de detalle de `ProcesoAdquisicion` muestra la lista de `CasoPagoProveedor` vinculados (lado inverso de la relación).
- El número de orden de compra (Mercado Público) queda explícitamente fuera de alcance de este change; se documenta como trabajo futuro dependiente de una integración no construida aún.

## Capabilities

### New Capabilities

(ninguna — este change extiende capacidades existentes, no introduce un dominio nuevo)

### Modified Capabilities

- `pago-proveedores-sgf`: el `CasoPagoProveedor` gana una relación opcional hacia `ProcesoAdquisicion` y una acción de vínculo manual auditada.
- `adquisiciones`: el `ProcesoAdquisicion` expone los `CasoPagoProveedor` vinculados en su vista de detalle.
- `seguridad-auditoria`: se agrega el permiso `pago_proveedores.vincular_adquisicion` y un evento de auditoría para crear/quitar el vínculo.

## Impact

- **Migración** nueva: columna `proceso_adquisicion_id` (nullable, FK, índice) en `casos_pago_proveedor`.
- **Modelos**: `CasoPagoProveedor` (relación `belongsTo` a `ProcesoAdquisicion`), `ProcesoAdquisicion` (relación `hasMany` a `CasoPagoProveedor`).
- **Backend**: nuevo controlador/acción para vincular/desvincular, Form Request de validación, Policy o gate con el nuevo permiso, registro en `AuditLog`.
- **Frontend**: UI de búsqueda asistida en `resources/js/pages/pago-proveedores/casos/show.tsx`; bloque de "Casos vinculados" en `resources/js/pages/adquisiciones/procesos/show.tsx`.
- **Seeder de permisos**: agregar `pago_proveedores.vincular_adquisicion` al seeder de roles/permisos correspondiente.
- **Tests**: feature tests para vincular/desvincular, validación de permisos, y que la acción no dispare ninguna transición de workflow.
