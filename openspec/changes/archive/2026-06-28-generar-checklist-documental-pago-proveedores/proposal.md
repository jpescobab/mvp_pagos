## Why

El primer cambio de checklist documental activó la resolución real solo para Adquisiciones, dejando Pago de Proveedores explícitamente fuera de alcance ("mismo gap, sin tocar"). Con la subida/validación de documentos ya funcionando de forma genérica (por `Proceso`, no por módulo), ya no tiene sentido mantener esa asimetría: Pago de Proveedores es el módulo más maduro del sistema y es justamente donde más se necesita evidencia documental controlada (`aprobar_documentacion` ya exige `FACTURA` vía `documentos_requeridos`, pero esa exigencia nunca se refleja en ningún checklist visible). Cerrar esta paridad reutiliza el mismo resolutor y la misma UI ya construidos, sin alcance nuevo.

## What Changes

- Catálogo de `tipos_documento` para Pago de Proveedores: reutilizar los ya existentes (`FACTURA`, `ORDEN_COMPRA`, `CONTRATO`, `ACTA_RECEP`, `CERT_VIGENCIA`, `RESOLUCION`, `COMPROBANTE`) — todos ya están en `TiposDocumentoSeeder`, no se crea ninguno nuevo.
- Un `ConjuntoRequisitosDocumentales` para el workflow "pago_proveedores" con una matriz de `requisitos_documentales` (sin distinción por modalidad, ya que `CasoPagoProveedor` no tiene una).
- Wiring de `ResolutorChecklistDocumentalProceso` en `CasoPagoProveedorController::show()`, igual que ya existe en `ProcesoAdquisicionController::show()`.

Fuera de alcance: no se modifica el workflow interno de Pago de Proveedores ni sus transiciones; no se cambia `ResolutorChecklistDocumentalProceso` (ya soporta esto sin cambios, según diseñado en el cambio anterior).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `pago-proveedores-sgf`: se agrega el requisito de que el detalle de un caso de pago resuelva y muestre un checklist documental real, no vacío.

## Impact

- `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php`.
- Nuevo `database/seeders/RequisitosDocumentalesPagoProveedoresSeeder.php`.
- `database/seeders/DatabaseSeeder.php`.
- Tests nuevos en `tests/Feature/PagoProveedores/`.
