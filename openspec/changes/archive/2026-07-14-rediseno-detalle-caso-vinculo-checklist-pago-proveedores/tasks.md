## 1. Backend

- [x] 1.1 `app/Http/Controllers/Documentos/DocumentoProcesoController.php`: nuevo método `ver(Proceso $proceso, Documento $documento)`, sirve el archivo con `response()->file()` (disposition inline)
- [x] 1.2 `routes/documentos.php`: `GET procesos/{proceso}/documentos/{documento}/ver` (`procesos.documentos.ver`)
- [x] 1.3 `app/Services/Documentos/ResolutorChecklistDocumentalProceso.php`: `requisitosAplicables()` agrega `whereRelation('tipoDocumento', 'activo', true)`
- [x] 1.4 Migración `2026_07_14_140000_change_requisito_documental_id_cascade_on_checklist_items.php`: `checklist_documental_proceso_items.requisito_documental_id` de `restrictOnDelete()` a `cascadeOnDelete()`
- [x] 1.5 Test: `GET procesos.documentos.ver` responde `200` con `Content-Disposition` sin `attachment`

## 2. Frontend

- [x] 2.1 `resources/js/components/pago-proveedores/checklist-documental-card.tsx`: iconos `Eye` (ver embebido) y `Unlink` (desvincular, solo con `documentos.gestionar`) por ítem con documento vinculado, en vez del enlace de texto "Ver documento"
- [x] 2.2 `resources/js/pages/pago-proveedores/casos/show.tsx`: reestructurar cabecera (monto destacado), layout 25/75 de "Clasificación y expediente"/"Financiero", y fila de checklist + vista previa embebida (`iframe`) del documento seleccionado
- [x] 2.3 `resources/js/components/pago-proveedores/transiciones-sidebar-card.tsx`: ajustes de layout asociados a la reestructuración

## 3. Verificación

- [x] 3.1 `composer test` (Pint, PHPStan, Pest) sin errores
- [x] 3.2 `npm run types:check`, `npm run lint:check`, `npm run build` sin errores
- [x] 3.3 Verificado en el navegador: abrir un caso con documentos vinculados, ver uno embebido sin que se descargue, desvincularlo desde el checklist, y confirmar que la matriz de requisitos documentales permite marcar "no aplica" sobre un requisito ya usado por un checklist existente sin error
