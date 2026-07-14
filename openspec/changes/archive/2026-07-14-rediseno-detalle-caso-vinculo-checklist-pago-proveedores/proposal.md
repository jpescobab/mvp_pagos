## Why

La vista de detalle de un caso de pago (`resources/js/pages/pago-proveedores/casos/show.tsx`) creció orgánicamente hasta perder jerarquía visual: el monto quedaba perdido entre otros datos, y el checklist documental solo ofrecía un enlace de texto ("Ver documento") que forzaba la descarga en vez de dejar revisar el archivo sin salir de la página. Al reestructurar la página aparecieron además dos bugs bloqueando comportamiento ya especificado: la matriz de requisitos documentales (`administracion-requisitos-documentales-pago-proveedores`) no podía marcar "no aplica" sobre un requisito que ya tenía `checklist_documental_proceso_items` cacheados (violación de foreign key), y el resolutor del checklist seguía mostrando documentos de un `TipoDocumento` ya desactivado desde el admin de requisitos.

**Nota de proceso**: este change se documenta después de implementar y mergear el código (commit `3c15b74`, PR #8) — el trabajo se hizo directamente sin pasar primero por `/opsx:propose`, saltándose el flujo obligatorio del harness. Este proposal, su spec delta y su archivado son retroactivos, para que `openspec/specs/` quede consistente con el comportamiento real antes de seguir agregando funcionalidad sobre esta misma vista.

## What Changes

- Reestructura la página de detalle de caso: cabecera con el monto destacado y datos reubicados, secciones "Clasificación y expediente" / "Financiero" en layout 25/75, y una fila con el checklist documental junto a una vista previa embebida del documento seleccionado.
- Nuevo endpoint `procesos.documentos.ver` (`GET`, disposition inline vía `response()->file()`) para que la vista previa embeba el archivo en un iframe en vez de descargarlo, siguiendo el mismo patrón que `RevisionVerDocumentoController::show()` ya usa en Revisión de Pagos.
- El checklist documental permite, directamente desde cada ítem: ver el documento vinculado (icono, embebido) y desvincularlo (icono, solo con `documentos.gestionar`), sin salir de la página ni navegar a otra sección.
- Fix: `ResolutorChecklistDocumentalProceso::requisitosAplicables()` ahora excluye los `requisitos_documentales` cuyo `TipoDocumento` tiene `activo = false`, en vez de seguir mostrándolos en casos reales aunque el tipo ya haya desaparecido del admin de requisitos.
- Fix: `checklist_documental_proceso_items.requisito_documental_id` pasa de `restrictOnDelete()` a `cascadeOnDelete()` — esos items son un caché regenerable de la resolución del checklist, no evidencia ni trazabilidad, así que ya no deben bloquear la eliminación del `RequisitoDocumental` que los originó (esto es lo que impedía "no aplica" en la matriz).

## Capabilities

### Modified Capabilities

- `documentos-expediente-variable`: nuevo endpoint de vista embebida (`ver`) además de la descarga existente; el resolutor del checklist ahora filtra por `TipoDocumento.activo`.
- `paginas-pago-proveedores`: el checklist del detalle de caso permite ver (embebido) y desvincular un documento directamente desde cada ítem.
- `administracion-requisitos-documentales-pago-proveedores`: marcar una celda de la matriz como "no aplica" ya no falla con un error de foreign key cuando existen `checklist_documental_proceso_items` cacheados que referencian ese requisito.

## Impact

- **Backend**: `app/Http/Controllers/Documentos/DocumentoProcesoController.php` (nuevo método `ver()`), `routes/documentos.php` (ruta `procesos.documentos.ver`), `app/Services/Documentos/ResolutorChecklistDocumentalProceso.php`, migración `2026_07_14_140000_change_requisito_documental_id_cascade_on_checklist_items.php`.
- **Frontend**: `resources/js/pages/pago-proveedores/casos/show.tsx` (reestructurado), `resources/js/components/pago-proveedores/checklist-documental-card.tsx` (iconos ver/desvincular, ya no enlace de texto).
- **Tests**: `tests/Feature/Documentos/SubirVincularDocumentoProcesoTest.php` (nuevo test: `ver` responde con disposition no-attachment).
- Sin migraciones de datos ni cambios de permisos nuevos — reutiliza `documentos.gestionar` ya existente.
