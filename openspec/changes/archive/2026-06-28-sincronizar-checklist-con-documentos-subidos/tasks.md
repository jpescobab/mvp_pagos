## 1. Resolutor

- [x] 1.1 En `ResolutorChecklistDocumentalProceso::resolve()`, construir un mapa `tipo_documento_id => Documento` a partir de los `VinculoDocumento` activos del proceso (eligiendo, ante duplicados de tipo, el vinculado más recientemente).
- [x] 1.2 Al crear cada `checklist_documental_proceso_item`, completar `documento_id` con el documento del mapa que coincida (si existe) y `estado_cumplimiento` según `Documento::estadoVigente()`: `cargado` si es `pendiente`, o el valor tal cual si es `valido`/`rechazado`. Sin coincidencia, mantener `documento_id: null` y `estado_cumplimiento: 'pendiente'` (comportamiento actual).

## 2. Resource y frontend

- [x] 2.1 Agregar `documento_id` al item del checklist en `App\Http\Resources\PagoProveedores\ProcesoResource`.
- [x] 2.2 Actualizar `ChecklistItem` en `resources/js/types/pago-proveedores.ts` con `documento_id: number | null`.
- [x] 2.3 En `pago-proveedores/casos/show.tsx` y `adquisiciones/procesos/show.tsx`, cuando un item del checklist tenga `documento_id`, mostrar un enlace "Ver documento" hacia la descarga (mismo endpoint `procesos.documentos.descargar`).

## 3. Tests

- [x] 3.1 Feature test: un requisito sin ningún documento subido resuelve `estado_cumplimiento: 'pendiente'` y `documento_id: null` (regresión del comportamiento ya existente).
- [x] 3.2 Feature test: un requisito con un documento subido (sin validar) resuelve `estado_cumplimiento: 'cargado'` y `documento_id` apuntando a ese documento.
- [x] 3.3 Feature test: un requisito cuyo documento ya tiene un evento de validación `valido` o `rechazado` resuelve ese mismo valor en `estado_cumplimiento`.
- [x] 3.4 Feature test: con dos documentos activos del mismo `tipo_documento_id`, el item queda asociado al más reciente.
- [x] 3.5 Feature test: el detalle de un proceso (HTTP) expone `documento_id` en los items del checklist cuando corresponde.

## 4. Validación

- [x] 4.1 Ejecutar `composer test` (incluye `lint:check`, `types:check` y la suite Pest).
- [x] 4.2 Ejecutar `npm run lint:check` y `npm run types:check`.
- [x] 4.3 Probar manualmente en el navegador: subir un documento del tipo "Contrato" a `ADQ-DEMO-001` y verificar que el item "Contrato" del checklist cambia de `pendiente` a `cargado` con un enlace "Ver documento".
