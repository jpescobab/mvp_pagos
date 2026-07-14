## 1. Reclasificar documentos vinculados (backend)

- [x] 1.1 `app/Http/Requests/Documentos/ReclasificarDocumentoRequest.php`: `tipo_documento_id` required, `exists:tipos_documento,id` + `activo=true`
- [x] 1.2 `app/Services/Documentos/GestorDocumentoProceso.php`: nuevo método `reclasificar(Documento $documento, TipoDocumento $tipoDocumento): void`
- [x] 1.3 `app/Http/Controllers/Documentos/DocumentoProcesoController.php`: nuevo método `reclasificar(Proceso $proceso, Documento $documento, ReclasificarDocumentoRequest $request)`, mismo `Gate::authorize('gestionarDocumentos', $proceso)` que las demás acciones; verificar que el documento tenga un `VinculoDocumento` activo con ese proceso antes de reclasificar (404/403 si no)
- [x] 1.4 `routes/documentos.php`: nueva ruta `PATCH {proceso}/documentos/{documento}/tipo-documento` → `procesos.documentos.tipo-documento.store` (o nombre consistente con el resto del grupo)
- [x] 1.5 Test: reclasificar con `documentos.gestionar` actualiza `tipo_documento_id` (la siguiente resolución del checklist ya está cubierta por el resolutor existente, no requiere test adicional)
- [x] 1.6 Test: usuario sin `documentos.gestionar` recibe 403
- [x] 1.7 Test: reclasificar un documento sin vínculo activo con el proceso de la URL es rechazado (404)

## 2. Checklist: vincular documentos huérfanos (backend + frontend)

- [x] 2.1 `app/Http/Resources/PagoProveedores/ProcesoResource.php::mapDocumentosVinculados()`: agregar `tipo_documento_id` y `coincide_checklist: bool` (comparado contra el set de `tipo_documento_id` de `checklist.items`) a cada documento
- [x] 2.2 `resources/js/types/pago-proveedores.ts`: `DocumentoVinculado` gana `tipo_documento_id`/`coincide_checklist`
- [x] 2.3 Correr `php artisan wayfinder:generate --with-form` tras la nueva ruta
- [x] 2.4 `resources/js/pages/pago-proveedores/casos/show.tsx`: en cada ítem pendiente del checklist, junto al `<input type="file">` ya existente, agregar un `<Select>` con los documentos del caso donde `coincide_checklist === false`, y un botón "Vincular" que llama al endpoint de reclasificación con el `tipo_documento_id` del ítem; ocultar el control si no hay documentos huérfanos
- [x] 2.5 `resources/js/pages/pago-proveedores/casos/show.tsx`: en la sección "Documentos", agregar un `Badge` "Sin clasificar" en los documentos con `coincide_checklist === false`
- [x] 2.6 Test Inertia: el detalle de un caso con un documento vinculado que no coincide con el checklist expone `coincide_checklist: false` para ese documento
- [x] 2.7 Verificar en el navegador: un caso real con documentos "Otro Documento" sin clasificar permite vincular uno al ítem "Factura" del checklist y el ítem pasa a "cargado"

## 3. Indicador "listo para Egreso" en el detalle de importación SGF

- [x] 3.1 `app/Http/Controllers/Sgf/ImportacionSgfController.php::show()`: ampliar el `with()` de los casos vinculados a `['proveedor', 'proceso.estadoActual', 'proceso.checklist.items', 'proceso.tipoProcesoPago', 'registrosContablesCgu']`; invocar `ResolutorChecklistDocumentalProceso::resolve()` por cada caso de la corrida (mismo conjunto `pago_proveedores`) antes de construir la respuesta
- [x] 3.2 `app/Http/Resources/Sgf/ImportacionSgfResource.php::mapSnapshots()`: agregar `listo_para_egreso` (booleano derivado según el criterio de `design.md`) por snapshot con caso vinculado
- [x] 3.3 `ImportacionSgfResource::resumen()`: agregar `casos_listos`/`casos_pendientes`
- [x] 3.4 `resources/js/types/sgf.ts`: reflejar `listo_para_egreso` en `SnapshotSgfResumen` y `casos_listos`/`casos_pendientes` en `ResumenImportacionSgf`
- [x] 3.5 Test: un caso con tipo de proceso clasificado, Traspaso registrado, checklist obligatorio completo y proveedor identificado se marca `listo_para_egreso: true`
- [x] 3.6 Test: un caso al que le falta cualquiera de esos 4 requisitos se marca `listo_para_egreso: false` (un test por requisito faltante)
- [x] 3.7 Test: el resumen de la corrida cuenta correctamente `casos_listos`/`casos_pendientes`

## 4. UI del detalle de importación SGF

- [x] 4.1 `resources/js/pages/sgf/importaciones/show.tsx`: nueva tarjeta de resumen "Casos listos para Egreso: X / Y"
- [x] 4.2 `resources/js/pages/sgf/importaciones/show.tsx`: badge "Listo"/"Pendiente" por snapshot con caso vinculado, junto al link "Ver caso"
- [x] 4.3 `resources/js/pages/sgf/importaciones/show.tsx`: botón "Continuar a Asignar Egreso" → navega a `egresosCgu.create().url` con el `trabajo_integracion_id` de la corrida como query param; deshabilitado si `casos_listos === 0`

## 5. Preselección en el formulario de creación de Egreso CGU

- [x] 5.1 `app/Http/Controllers/PagoProveedores/EgresoCguController.php::create()`: aceptar `Request $request`; si viene `trabajo_integracion_id`, resolver los `sgf_id` de esa corrida y aplicar `whereIn('sgf_id', $sgfIds)` sobre la lista ya filtrada por `whereDoesntHave('egresoCguItems')`; sin el parámetro, comportamiento actual sin cambios. Pasar también `trabajoIntegracionId` y el flag `listo` por caso (mismo criterio del punto 3.1)
- [x] 5.2 `resources/js/types/pago-proveedores.ts`: `CasoSeleccionable` gana `listo?: boolean`
- [x] 5.3 `resources/js/pages/pago-proveedores/egresos-cgu/crear.tsx`: si viene `trabajoIntegracionId`, mostrar encabezado contextual y preseleccionar el `Set` de selección inicial solo con los casos `listo === true`
- [x] 5.4 Test: `EgresoCguController::create()` sin `trabajo_integracion_id` devuelve los mismos casos que hoy (sin regresión)
- [x] 5.5 Test: `EgresoCguController::create()` con `trabajo_integracion_id` devuelve solo los casos de esa corrida sin egreso asignado
- [x] 5.6 Verificar en el navegador: desde el detalle de una importación con casos listos, "Continuar a Asignar Egreso" abre el formulario con esos casos preseleccionados y el resto visible sin marcar

## 6. Verificación

- [x] 6.1 Correr `composer test` (lint:check, types:check, php artisan test) y `vendor/bin/pint --dirty --format agent`
- [x] 6.2 `npm run build` + `npm run types:check`
- [x] 6.3 Verificar en el navegador de punta a punta: importar/usar una corrida real, completar tipo de proceso + Traspaso + checklist (incluida vinculación de un documento huérfano) de al menos un caso, confirmar que aparece "Listo" en el detalle de la importación, y que "Continuar a Asignar Egreso" lo preselecciona correctamente
