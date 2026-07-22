## Context

La página de detalle del caso (`CasoPagoProveedorController::show` → `casos/show.tsx`) carga el proceso con `vinculosDocumento.documento.versiones` y regenera el checklist en cada visita (`ResolutorChecklistDocumentalProceso::resolve`). El checklist se serializa en `ProcesoResource` (bloque `checklist.items` con `documento_id` pero sin nombre de archivo) y la lista de documentos vinculados (`mapDocumentosVinculados`, solo `activo=true`, con `nombre_archivo` y `coincide_checklist`). El card `checklist-documental-card.tsx` ofrece, por ítem pendiente, subir un archivo y — vía `documentosHuerfanos` (activos, `!coincide_checklist`) — vincular uno ya importado (que llama a `reclasificar`). `desvincular` es un soft-unlink (`GestorDocumentoProceso::desvincular` → `activo=false`). El visor (`show.tsx`) usa un estado React `documentoPreviewId` directo contra la ruta `documentos.ver` (que sirve cualquier documento por id, sin validar vínculo activo).

## Goals / Non-Goals

**Goals:**
- Re-vincular el MISMO documento tras desvincularlo, reactivando su vínculo (sin re-subir), desde "vincula uno ya importado".
- Que el visor siga al documento vigente y se limpie cuando el documento previsualizado deja de estar vinculado.
- Mostrar el nombre real del archivo por ítem del checklist con documento vinculado.
- Mostrar el número de factura (`caso.numero`) en la cabecera cuando exista.

**Non-Goals:**
- No cambiar la semántica de `desvincular` (sigue soft-unlink; no borra).
- Sin migraciones ni cambios de esquema.
- No tocar el workflow ni `TransicionWorkflowService`.
- No cambiar el endpoint `documentos.ver` (sirve por id; el control de qué se muestra es del frontend/estado).

## Decisions

### 1. Reactivación en el Service, expuesta como endpoint delgado
Se agrega `GestorDocumentoProceso::reactivarYReclasificar(Proceso $proceso, Documento $documento, TipoDocumento $tipo)`: dentro de una transacción, ubica el `VinculoDocumento` inactivo del documento en ese proceso, lo pone `activo=true`, y reclasifica el documento al tipo elegido. El controlador expone una ruta nueva (p. ej. `PATCH procesos/{proceso}/documentos/{documento}/reactivar`) delgada: `Gate::authorize('gestionarDocumentos', $proceso)` + delega en el Service.

Alternativa descartada: reutilizar `reclasificar` (exige `activo=true`, `abort_unless`); ampliarlo para reactivar mezclaría dos intenciones. Un método explícito de reactivación es más claro y testeable.

### 2. Documentos re-vinculables = vínculos inactivos del proceso
`ProcesoResource` expone, además de `documentos` (activos), la lista de documentos con vínculo **inactivo** del proceso (`vinculos_documento.activo=false`), con `documento_id`, `tipo_documento`, `nombre_archivo`. El frontend los ofrece en el mismo control "vincula uno ya importado" junto a los huérfanos activos; elegir un re-vinculable llama al endpoint de reactivación, elegir un huérfano activo sigue llamando a `reclasificar` (sin cambios). Para exponerlos sin N+1, el proceso ya carga `vinculosDocumento.documento.versiones`; se mapean ambos subconjuntos (activo/inactivo) desde la misma colección ya cargada.

### 3. Nombre de archivo por ítem del checklist en el backend
En `ProcesoResource`, el bloque `checklist.items` gana `nombre_archivo`: se construye un mapa `documento_id → nombre_archivo` desde los vínculos ya cargados y se resuelve por `item.documento_id` (null si el ítem está pendiente). React solo lo renderiza junto al tipo. No se recalcula el nombre en el cliente (respeta "los requisitos los entrega el backend").

### 4. El visor sigue al documento vigente (estado derivado en el cliente)
En `show.tsx`, tras cada recarga de props (Inertia), si `documentoPreviewId` ya no corresponde a un documento actualmente vinculado (ni al ítem que lo originó), se limpia (`setDocumentoPreviewId(null)`) vía un `useEffect` que observa los documentos/checklist vigentes. Así, al desvincular el documento en vista previa, el visor se limpia; al re-vincular y pulsar "Ver", el visor muestra el documento vigente del ítem (el id nuevo). Es una corrección de estado en el cliente; el endpoint `ver` no cambia.

### 5. Renombrar "Número SGF" → "N° DTE" en la cabecera
El valor ya viaja en el payload (`caso.numero`) y hoy se muestra en la cabecera de `show.tsx` bajo la etiqueta "Número SGF". Se **renombra** esa etiqueta a "N° DTE" (Documento Tributario Electrónico), que es lo que ese número representa según el origen SGF (verificado: el snapshot dice "FACTURA N°293819" y el documento es `FAE-293819.pdf`). No se agrega un campo nuevo (se evita duplicar el valor) ni se requieren cambios de backend.

## Risks / Trade-offs

- **[Renombrar puede confundir a quien buscaba "Número SGF"]** → La etiqueta "Número SGF" era imprecisa (el identificador del proceso SGF es `sgf_id`, que se muestra aparte). "N° DTE" describe correctamente `caso.numero`. Bajo riesgo; el `sgf_id` sigue visible como identificador SGF.
- **[Un documento pudo tener múltiples vínculos históricos en el proceso]** → La reactivación ubica "el" vínculo inactivo del documento en el proceso; si hubiera más de uno, se reactiva el más reciente. En la práctica hay un vínculo por (documento, proceso). Se cubre con test del caso normal.
- **[El visor servía cualquier documento por id]** → No se cambia el endpoint (fuera de alcance); el visor se corrige por estado en el cliente. Un usuario que arme la URL a mano aún podría ver un documento desvinculado, pero eso es preexistente y no es parte de este cambio (el endpoint no expone datos sensibles nuevos: ya servía por id).

## Migration Plan

Sin migraciones. Deploy backend + build frontend + `wayfinder:generate`. Rollback: revertir el change; los datos no cambian de forma.

## Open Questions

Ninguna: la decisión de (c) quedó cerrada (renombrar "Número SGF" → "N° DTE"). El resto (reactivación en Service, re-vinculables desde vínculos inactivos, nombre por ítem en el Resource, visor derivado en el cliente) también están cerradas.
