## 1. Servicio de dominio

- [x] 1.1 Agregar `subirNuevaVersion(Documento $documento, UploadedFile $archivo, User $usuario): VersionDocumento` a `GestorDocumentoProceso`, calculando `numero_version` como `$documento->versiones()->max('numero_version') + 1` dentro de una transacción.

## 2. Backend HTTP

- [x] 2.1 Crear Form Request `SubirNuevaVersionDocumentoRequest` (mismas reglas que `SubirDocumentoProcesoRequest` para el campo `archivo`).
- [x] 2.2 Agregar `nuevaVersion(Proceso $proceso, Documento $documento, SubirNuevaVersionDocumentoRequest $request)` a `DocumentoProcesoController`, autorizando con `Gate::authorize('gestionarDocumentos', $proceso)`.
- [x] 2.3 Agregar la ruta `POST /procesos/{proceso}/documentos/{documento}/versiones` en `routes/documentos.php`, nombrada `procesos.documentos.versiones.store`.

## 3. Frontend

- [x] 3.1 Regenerar rutas Wayfinder (`--with-form`).
- [x] 3.2 En `pago-proveedores/casos/show.tsx` y `adquisiciones/procesos/show.tsx`, agregar un control "Nueva versión" (input de archivo) por documento listado en la sección "Documentos".

## 4. Tests

- [x] 4.1 Feature test: subir una nueva versión de un documento existente crea una `VersionDocumento` con `numero_version` consecutivo y no crea ningún `Documento` ni `VinculoDocumento` nuevo.
- [x] 4.2 Feature test: subir una nueva versión de un documento que ya tiene un evento de validación no altera su `estadoVigente()`.
- [x] 4.3 Feature test: usuario sin `documentos.gestionar` no puede subir una nueva versión, y queda auditado en `security_audit_logs`.
- [x] 4.4 Feature test: descargar un documento con dos versiones sirve la más reciente.

## 5. Validación

- [x] 5.1 Ejecutar `composer test`.
- [x] 5.2 Ejecutar `npm run lint:check` y `npm run types:check`.
- [x] 5.3 Probar manualmente en el navegador: subir una nueva versión de un documento ya vinculado a `ADQ-DEMO-001` y verificar que no aparece duplicado en la lista de "Documentos".
