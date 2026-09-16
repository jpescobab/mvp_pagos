## Why

Cuando el scraper SGF descarga los documentos de un caso, los guarda en `storage/app/private/sgf-documentos/{sgfId}/` y normalmente cada uno termina como una fila `Documento` vinculada al `Proceso`. Pero existe un caso real (no hipotético, confirmado en `sgf-scraper.js::descargarDocumentosDeFila()`): si algo falla entre que el archivo se escribe en disco y se cierra el popup de descarga, el archivo queda huérfano en el filesystem — nunca se reporta de vuelta a Laravel, así que nunca se crea su `Documento`, y el usuario no tiene forma de encontrarlo ni vincularlo desde la aplicación. Hoy el único remedio es buscar el archivo a mano en el filesystem del servidor y no hay ningún flujo para incorporarlo al expediente sin volver a subirlo.

## What Changes

- Nueva sección en el checklist documental de un `CasoPagoProveedor`: lista los archivos presentes en `storage/app/private/sgf-documentos/{sgfId}/` que todavía no tienen una fila `VersionDocumento` (comparando por ruta relativa) — es decir, exactamente los que quedaron huérfanos en el filesystem.
- El selector "vincula uno ya importado" que ya existe por cada ítem pendiente del checklist se extiende para incluir también estos archivos sueltos, junto a los huérfanos/revinculables que ya ofrecía.
- Nuevo método genérico en `GestorDocumentoProceso` (`vincularArchivoExistente`) que crea `Documento`+`VersionDocumento`+`VinculoDocumento` a partir de un archivo que YA está en disco (calcula el hash del archivo existente, sin pedir que se vuelva a subir) — reutiliza el mismo mecanismo de evidencia/trazabilidad que ya usa `subirYVincular`.
- Nuevo endpoint `POST procesos/{proceso}/documentos/vincular-existente`, genérico a nivel de `Proceso` (no acoplado a SGF) — la ruta relativa que llega se revalida en el backend contra la lista real de archivos sueltos de ese caso específico (nunca se confía en la ruta que manda el cliente, para evitar acceder a archivos fuera de esa carpeta).
- La lista de "archivos sueltos" se calcula solo para casos con `sgf_id` (todos los importados desde SGF); no aplica a otros dominios que usan `procesos/{proceso}/documentos` (Contratos, CDP, etc.).

## Capabilities

### New Capabilities
- `vincular-archivos-sgf-sueltos`: detectar y vincular al expediente los archivos que el scraper SGF dejó en disco sin registrar como `Documento`, sin necesidad de volver a subirlos ni de buscarlos manualmente en el servidor.

### Modified Capabilities
(ninguna — se extiende el checklist documental existente y `GestorDocumentoProceso` con un método nuevo, sin cambiar el comportamiento ya documentado de subir/reclasificar/reactivar documentos)

## Impact

- Nuevo servicio `app/Services/Sgf/ArchivoSgfSueltoResolver.php` (o nombre equivalente), consultado desde `CasoPagoProveedorController::show()`.
- Modificación de `app/Services/Documentos/GestorDocumentoProceso.php` (método nuevo `vincularArchivoExistente`).
- Modificación de `app/Http/Controllers/Documentos/DocumentoProcesoController.php` (acción nueva `vincularExistente`) y `routes/documentos.php`.
- Modificación de `resources/js/components/pago-proveedores/checklist-documental-card.tsx` y `resources/js/pages/pago-proveedores/casos/show.tsx` (nueva prop, nuevo handler).
- Sin cambios al workflow del `CasoPagoProveedor` ni a la importación SGF existente.
