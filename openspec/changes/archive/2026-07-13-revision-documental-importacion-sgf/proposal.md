## Why

Dos gaps quedan entre "caso importado desde SGF" y "asignado a un Egreso CGU":

1. **Documentos ya importados no calzan con el checklist.** El conector Playwright de SGF ya descarga y vincula automáticamente los documentos reales de un caso al `Proceso` (`ConectorSgfPlaywrightService::vincularDocumento()`), pero su heurística de clasificación (`inferirTipoDocumento` en `sgf-scraper.js`) solo distingue Factura (prefijo `FAE-`) de "OTRO" — confirmado en producción: un caso real con 8 PDFs ya subidos muestra el checklist 100% "pendiente" porque ninguno calza por `tipo_documento_id`. `administrativo_finanzas` no tiene forma de corregir esa clasificación sin volver a subir el archivo a mano.
2. **No hay vista de revisión por corrida de importación, ni forma de avanzar solo los casos listos.** `EgresoCguController::create()` lista TODOS los casos pendientes del sistema sin agrupar por importación, obligando a buscar uno por uno. El detalle de una importación SGF (`sgf/importaciones/show.tsx`) es de solo lectura, sin indicar qué casos ya están listos para pasar a Asignar Egreso.

## What Changes

- Se agrega una acción de reclasificación de documento (`tipo_documento_id`) sobre un `Documento` ya vinculado a un `Proceso`, gateada por `documentos.gestionar` (mismo permiso que ya gestiona documentos).
- El checklist documental del caso ofrece, en cada ítem pendiente, además del atajo de subida ya existente, un segundo control para vincular un documento del caso que no calza con ningún ítem actual ("huérfano"), sin volver a subirlo.
- El detalle de una importación SGF expone, por cada caso, un indicador derivado `listo_para_egreso` (tipo de proceso clasificado + Traspaso registrado + checklist obligatorio completo + proveedor identificado) y un resumen agregado (`casos_listos`/`casos_pendientes`).
- Un botón "Continuar a Asignar Egreso" en el detalle de la importación navega al formulario existente de creación de Egreso CGU, pre-filtrando y pre-seleccionando los casos de esa corrida que ya están listos (avance parcial: los no listos quedan disponibles para completarse después).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `documentos-expediente-variable`: se agrega la acción de reclasificar el tipo de un documento ya vinculado.
- `consulta-importaciones-sgf`: el detalle de una importación gana el indicador `listo_para_egreso` por caso y el resumen agregado.
- `paginas-pago-proveedores`: el checklist documental del caso gana el control de vinculación de documentos huérfanos; el formulario de creación de Egreso CGU acepta preselección desde una importación.

## Impact

- `app/Http/Requests/Documentos/ReclasificarDocumentoRequest.php` (nuevo)
- `app/Http/Controllers/Documentos/DocumentoProcesoController.php`, `app/Services/Documentos/GestorDocumentoProceso.php`, `routes/documentos.php`
- `app/Http/Resources/PagoProveedores/ProcesoResource.php`
- `app/Http/Controllers/Sgf/ImportacionSgfController.php`, `app/Http/Resources/Sgf/ImportacionSgfResource.php`
- `app/Http/Controllers/PagoProveedores/EgresoCguController.php`
- `resources/js/pages/pago-proveedores/casos/show.tsx`, `resources/js/pages/sgf/importaciones/show.tsx`, `resources/js/pages/pago-proveedores/egresos-cgu/crear.tsx`
- `resources/js/types/pago-proveedores.ts`, `resources/js/types/sgf.ts`
- Tests: `tests/Feature/Documentos/*`, `tests/Feature/Sgf/*`, `tests/Feature/PagoProveedores/*`
