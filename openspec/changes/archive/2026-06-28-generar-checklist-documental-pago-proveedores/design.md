## Context

El patrón ya está completamente probado en Adquisiciones (`generar-checklist-documental-adquisiciones`, archivado): un seeder crea un `ConjuntoRequisitosDocumentales` + `RequisitoDocumental` por workflow, y el controlador `show()` invoca `ResolutorChecklistDocumentalProceso::resolve()` antes de servir la respuesta. `ResolutorChecklistDocumentalProceso` ya soporta reglas sin `modalidad_id` (queda `null`, y el filtro `whereNull('modalidad_id')->orWhere(...)` lo trata como "aplica siempre"), así que no requiere ningún cambio para Pago de Proveedores, que no tiene modalidades.

`WorkflowPagoProveedoresSeeder` ya declara `documentos_requeridos: ['FACTURA']` en la transición `aprobar_documentacion` (validado por `ResolutorValidacionDocumental` contra `tipo_documento.codigo`, no contra el checklist) — confirma que `FACTURA` es, como mínimo, un documento real y obligatorio en este flujo.

## Goals / Non-Goals

**Goals:**
- Mismo resultado que Adquisiciones: el detalle de un caso de pago muestra un checklist real, no vacío.
- Reutilizar el catálogo de `tipos_documento` ya sembrado por `TiposDocumentoSeeder` (no crear códigos nuevos).

**Non-Goals:**
- No se condiciona ningún requisito por `estado_workflow_id` (p. ej. exigir `COMPROBANTE` solo una vez pagada) — primera aproximación plana, igual que se hizo para Adquisiciones; ajustable después sin tocar código.
- No se modifica el workflow ni sus transiciones.
- No se sincroniza nada nuevo en `ResolutorChecklistDocumentalProceso` — ya soporta esto desde el cambio anterior.

## Decisions

**D1 — Reglas sin `modalidad_id` (null).**
`CasoPagoProveedor`/su `Proceso` no tiene modalidad; los `requisito_documental` se crean con `modalidad_id: null`, que el resolutor ya interpreta como "aplica a cualquier modalidad" — comportamiento ya soportado, no requiere cambios.

**D2 — Matriz plana: `FACTURA`, `ACTA_RECEP`, `CERT_VIGENCIA`, `RESOLUCION`, `COMPROBANTE` obligatorios; `ORDEN_COMPRA`, `CONTRATO` opcionales.**
`FACTURA` obligatorio porque ya lo exige `aprobar_documentacion`. El resto es una aproximación razonable del expediente típico de un pago a proveedor (factura, acta de recepción conforme, vigencia del proveedor, resolución que autoriza el pago, comprobante de la transferencia), dejando `ORDEN_COMPRA`/`CONTRATO` como opcionales porque no todo pago tiene una orden de compra o contrato asociado (p. ej. servicios básicos recurrentes). Mismo criterio que D4 del cambio de Adquisiciones: aproximación inicial, ajustable.

**D3 — Wiring idéntico al de `ProcesoAdquisicionController::show()`.**
Mismo patrón: buscar el `ConjuntoRequisitosDocumentales` por código, `resolve()` si existe, recargar `proceso.checklist.items` antes de construir el Resource. No se introduce ninguna abstracción nueva para "no duplicar" ese bloque entre los dos controladores — son 5 líneas idénticas en dos controladores de dominios distintos, no amerita una clase compartida todavía.

## Risks / Trade-offs

- [Riesgo] La matriz D2 es una aproximación, no una definición normativa exacta → Mitigación: igual que en Adquisiciones, queda en datos de seeder, fácilmente ajustable.
- [Riesgo] Duplicar el bloque de wiring en dos controladores en vez de extraer un helper → Mitigación: aceptable mientras sean solo dos; si aparece un tercer módulo se evalúa extraer un trait/helper en ese momento, no antes (evitar abstracción prematura).

## Migration Plan

1. Crear `RequisitosDocumentalesPagoProveedoresSeeder`.
2. Registrarlo en `DatabaseSeeder` después de `WorkflowPagoProveedoresSeeder`.
3. Wirear `ResolutorChecklistDocumentalProceso` en `CasoPagoProveedorController::show()`.
4. Tests.

Sin cambios de esquema, sin rollback especial.
