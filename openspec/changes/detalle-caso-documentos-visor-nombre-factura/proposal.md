## Why

En la página de detalle de un caso de pago (`/pago-proveedores/casos/{id}`) hay tres fricciones al gestionar el expediente documental: (1) al desvincular un documento y querer volver a vincularlo no hay forma de re-vincular el mismo (el desvincular es soft — `activo=false` — y el documento desaparece del selector "vincula uno ya importado", que solo lista vínculos activos), y el visor de PDF queda mostrando el documento viejo porque su estado no se limpia; (2) el checklist muestra el tipo de documento pero no el nombre real del archivo vinculado, así que no se distingue qué PDF quedó cargado; (3) la cabecera no muestra el número de factura, que sí viene del origen SGF.

## What Changes

- **Re-vincular un documento desvinculado**: los documentos con vínculo **inactivo** (desvinculados) del proceso vuelven a estar disponibles en "vincula uno ya importado"; re-vincular uno **reactiva** su vínculo (`activo=true`) y lo reclasifica al tipo del ítem, en vez de exigir re-subir el archivo. `desvincular` sigue siendo soft-unlink (no se pierde trazabilidad).
- **Visor que sigue al documento vigente**: la vista previa embebida se limpia cuando el documento que estaba previsualizado deja de estar vinculado activamente (p. ej. tras desvincular), y muestra el documento vigente al elegir "Ver" de un ítem re-vinculado — nunca queda mostrando un PDF ya desvinculado o desalineado del ítem.
- **Nombre real del archivo en el checklist**: cada ítem del checklist con documento vinculado muestra, junto al tipo, el nombre real del archivo del documento vigente (resuelto por el backend).
- **N° DTE en la cabecera**: el campo de cabecera que hoy muestra `caso.numero` etiquetado como "Número SGF" se **renombra** a "N° DTE" (Documento Tributario Electrónico), que es lo que ese valor representa según el origen SGF (el número del DTE/factura). No se agrega un campo nuevo: se corrige la etiqueta del existente.

## Capabilities

### New Capabilities
<!-- Ninguna: se extiende la página de detalle de caso existente. -->

### Modified Capabilities
- `paginas-pago-proveedores`: se agregan a la página de detalle de un caso (requirement "Página de detalle de un caso con acciones de workflow") tres comportamientos nuevos, sin alterar los existentes: re-vinculación de documentos desvinculados (con reactivación del vínculo), el visor de vista previa que sigue al documento vigente, el nombre real del archivo por ítem del checklist, y el número de factura en la cabecera.

## Impact

- **Backend (Services; controlador liviano)**:
  - `app/Services/Documentos/GestorDocumentoProceso.php` — nueva operación de reactivación (reactivar `VinculoDocumento` a `activo=true` + reclasificar al tipo elegido); `desvincular` intacto (soft-unlink).
  - `app/Http/Controllers/Documentos/DocumentoProcesoController.php` — endpoint delgado para reactivar/re-vincular, gateado por `documentos.gestionar`.
  - `app/Http/Resources/PagoProveedores/ProcesoResource.php` — cada ítem del checklist expone `nombre_archivo` del documento vigente; se exponen los documentos con vínculo inactivo como re-vinculables.
- **Rutas**: `routes/documentos.php` agrega la ruta de reactivación; Wayfinder regenerado, helper tipado en React.
- **Frontend**:
  - `resources/js/components/pago-proveedores/checklist-documental-card.tsx` — muestra el nombre del archivo por ítem; ofrece los documentos re-vinculables (desvinculados) además de los huérfanos.
  - `resources/js/pages/pago-proveedores/casos/show.tsx` — el visor limpia/actualiza `documentoPreviewId` cuando el documento deja de estar vinculado; renombra la etiqueta "Número SGF" a "N° DTE" en la cabecera.
  - `resources/js/types/pago-proveedores.ts` — tipos (`nombre_archivo` por ítem, re-vinculables).
- **Sin migraciones**: se reutilizan `vinculos_documento.activo`, `documentos`, `versiones_documento`.
- **Harness**: controladores livianos; el soft-unlink no borra trazabilidad; no se toca `TransicionWorkflowService`.
- **Tests**: reactivación de un documento desvinculado (activo=true, gateada por `documentos.gestionar`); `nombre_archivo` por ítem; que la cabecera use la etiqueta "N° DTE" para `caso.numero`.
