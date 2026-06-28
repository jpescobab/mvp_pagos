## Context

`VersionDocumento` ya tiene `numero_version`, `ruta_archivo`, `nombre_archivo`, `tipo_mime`, `tamano_bytes`, `hash`, `subido_por`, sin `updated_at` (es inmutable, como `validaciones_documento`). `Documento::estadoVigente()` lee únicamente `validaciones_documento`, nunca `versiones_documento` — agregar una versión no interactúa con el estado de validación de ningún modo, lo cual ya es el comportamiento correcto para este cambio (no hay nada que adaptar ahí).

`GestorDocumentoProceso::subirYVincular()` ya existe y crea `Documento` + versión 1 + `VinculoDocumento` en una transacción; este cambio agrega un método hermano para el caso "ya existe el `Documento`, solo agrego una versión".

## Goals / Non-Goals

**Goals:**
- Subir una nueva versión de un `Documento` ya vinculado a un proceso, sin perder su historial de validaciones ni crear un `VinculoDocumento` duplicado.
- Mismas reglas de validación de archivo que la subida inicial (tipo MIME, tamaño).

**Non-Goals:**
- No se resetea ni se toca `validaciones_documento` al subir una nueva versión (Non-Goal explícito, ver proposal).
- No se permite "elegir" qué versión descargar — `descargar()` ya sirve siempre la última versión (`versiones()->latest('numero_version')->first()`, sin cambios necesarios ahí).
- No se valida que el nuevo archivo sea "del mismo tipo" que el original (p. ej. mismo tipo MIME) — simplemente debe pasar las mismas reglas genéricas (`mimes:pdf,jpg,jpeg,png`, tamaño máximo).

## Decisions

**D1 — Nuevo método `subirNuevaVersion(Documento $documento, UploadedFile $archivo, User $usuario): VersionDocumento` en `GestorDocumentoProceso`.**
Calcula `numero_version` como `$documento->versiones()->max('numero_version') + 1` dentro de una transacción (evita condiciones de carrera entre dos subidas simultáneas del mismo documento). Reutiliza el mismo servicio que ya tiene `subirYVincular`/`desvincular`/`descargarRutaArchivo`, en vez de crear un servicio nuevo — son todas operaciones sobre el mismo agregado documental.

**D2 — Ruta anidada bajo el documento, no bajo el vínculo.**
`POST /procesos/{proceso}/documentos/{documento}/versiones`. El `{proceso}` en la URL es solo para mantener la autorización vía `ProcesoPolicy` consistente con el resto de `routes/documentos.php`; la operación en sí no depende de un vínculo específico (un documento puede estar vinculado a varios procesos en teoría, aunque hoy solo se vincula a uno por vez vía la UI).

**D3 — Mismo método del controlador existente (`DocumentoProcesoController`), no uno nuevo.**
Agregar `nuevaVersion()` a `DocumentoProcesoController` en vez de crear un controlador dedicado — es la misma familia de acciones (`store`, `descargar`, `destroy`) sobre el mismo recurso, ya autorizadas todas con `gestionarDocumentos`.

## Risks / Trade-offs

- [Riesgo] Sin reseteo de estado de validación, un documento "corregido" tras un rechazo sigue mostrando `rechazado` hasta que alguien lo valide de nuevo explícitamente, lo que podría parecer "no refleja la corrección" → Mitigación: es el comportamiento correcto e intencional — la validación humana sigue siendo necesaria después de cualquier corrección; automatizar el reseteo sería asumir que la nueva versión es válida sin revisión, contrario al espíritu del control documental.

## Migration Plan

1. Agregar `subirNuevaVersion()` a `GestorDocumentoProceso`.
2. Agregar Form Request (reutilizable o nuevo) y método `nuevaVersion()` en `DocumentoProcesoController`.
3. Agregar la ruta en `routes/documentos.php`.
4. UI: botón "Nueva versión" por documento.
5. Tests.

Sin cambios de esquema, sin rollback especial.
