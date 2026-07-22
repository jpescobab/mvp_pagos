## 1. Reactivación de documentos desvinculados (backend)

- [x] 1.1 En `app/Services/Documentos/GestorDocumentoProceso.php`, agregar `reactivarYReclasificar(Proceso $proceso, Documento $documento, TipoDocumento $tipo)`: en una `DB::transaction`, ubica el `VinculoDocumento` inactivo del documento en ese proceso, lo pone `activo=true` y reclasifica el documento al `tipo`. `desvincular` queda intacto (soft-unlink).
- [x] 1.2 En `app/Http/Controllers/Documentos/DocumentoProcesoController.php`, agregar `reactivar(Proceso $proceso, Documento $documento, ReclasificarDocumentoRequest $request)` delgado: `Gate::authorize('gestionarDocumentos', $proceso)`, valida que exista un vínculo inactivo de ese documento en el proceso (abort_unless 404 si no), y delega en el Service. Reutilizar `ReclasificarDocumentoRequest` (mismo `tipo_documento_id`).
- [x] 1.3 Agregar la ruta `PATCH procesos/{proceso}/documentos/{documento}/reactivar` en `routes/documentos.php` (nombre `procesos.documentos.reactivar`) y regenerar Wayfinder (`php artisan wayfinder:generate --with-form`).

## 2. Payload: nombre de archivo por ítem + documentos re-vinculables (backend)

- [x] 2.1 En `app/Http/Resources/PagoProveedores/ProcesoResource.php`, agregar `nombre_archivo` a cada ítem de `checklist.items`: construir un mapa `documento_id → nombre_archivo` desde `vinculosDocumento` ya cargados (versión vigente) y resolverlo por `item.documento_id` (null si pendiente).
- [x] 2.2 En el mismo Resource, exponer los documentos con vínculo **inactivo** del proceso como lista `documentos_revinculables` (`documento_id`, `tipo_documento`, `nombre_archivo`), mapeada desde la misma colección `vinculosDocumento` (subconjunto `activo=false`), sin consultas adicionales.

## 3. Frontend

- [x] 3.1 En `resources/js/types/pago-proveedores.ts`, agregar `nombre_archivo` al tipo de ítem del checklist y el tipo de `documentos_revinculables` en el proceso.
- [x] 3.2 En `resources/js/components/pago-proveedores/checklist-documental-card.tsx`, mostrar el `nombre_archivo` del ítem junto al tipo de documento cuando el ítem tenga documento vinculado; en el control "vincula uno ya importado" de un ítem pendiente, incluir los documentos re-vinculables además de los huérfanos, y al elegir un re-vinculable llamar al endpoint de reactivación (helper Wayfinder), al elegir un huérfano seguir usando `reclasificar` como hoy.
- [x] 3.3 En `resources/js/pages/pago-proveedores/casos/show.tsx`, agregar un `useEffect` que limpie `documentoPreviewId` cuando ese id ya no corresponde a un documento actualmente vinculado del proceso (tras desvincular), y cablear la acción de reactivación (`vincularHuerfano`/nueva `reactivarDocumento`) hacia el nuevo endpoint. El visor sigue mostrando el documento vigente al pulsar "Ver".
- [x] 3.4 En `resources/js/pages/pago-proveedores/casos/show.tsx`, renombrar la etiqueta de cabecera "Número SGF" a "N° DTE" (mismo valor `caso.numero`, mismo fallback "—" cuando sea nulo). No se agrega un campo nuevo.

## 4. Tests

- [x] 4.1 Feature: re-vincular (reactivar) un documento previamente desvinculado pone su vínculo en `activo=true` y lo reclasifica al tipo del ítem; el ítem del checklist queda con ese documento vigente.
- [x] 4.2 Feature: el endpoint de reactivación exige `documentos.gestionar` (sin el permiso, denegado; el vínculo permanece inactivo); reactivar un documento sin vínculo inactivo en el proceso devuelve 404.
- [x] 4.3 Feature: `ProcesoResource` expone `nombre_archivo` por ítem del checklist con documento vinculado y null cuando está pendiente; expone `documentos_revinculables` con los desvinculados.
- [x] 4.4 Feature: la respuesta del detalle del caso incluye `caso.numero` (assert Inertia sobre la prop); el render de la etiqueta "N° DTE" se cubre con types/lint (el valor y el fallback ya están cubiertos por la cabecera existente).

## 5. Validación y cierre

- [x] 5.1 `vendor/bin/pint --dirty --format agent` sobre los PHP tocados.
- [x] 5.2 `composer test` (config:clear + lint:check + types:check + Pest) y `npm run types:check` + `npm run lint:check` para el frontend.
- [x] 5.3 Revisar los controllers tocados contra la regla de controladores livianos; verificar en la pantalla real el ciclo desvincular → re-vincular → Ver (visor muestra el documento vigente), el nombre de archivo por ítem, y el N° de factura en la cabecera.
