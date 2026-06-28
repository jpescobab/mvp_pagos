## 1. Permiso

- [x] 1.1 Agregar el permiso `documentos.gestionar` en `RolesAndPermissionsSeeder`, asignado a `superadmin` y `admin`.

## 2. Servicio de dominio

- [x] 2.1 Crear `App\Services\Documentos\GestorDocumentoProceso` con `subirYVincular(Proceso $proceso, UploadedFile $archivo, TipoDocumento $tipoDocumento, User $usuario): VinculoDocumento` (crea `Documento`, `VersionDocumento` n.º 1 con `Storage::disk('local')->putFile()`, y `VinculoDocumento` activo, en una transacción).
- [x] 2.2 Agregar `desvincular(VinculoDocumento $vinculo): void` al mismo servicio (marca `activo = false`).

## 3. Backend HTTP

- [x] 3.1 Crear Form Request `SubirDocumentoProcesoRequest` (`archivo`: `required|file|mimes:pdf,jpg,jpeg,png|max:10240`; `tipo_documento_id`: `required|exists:tipos_documento,id` filtrando `activo=1`).
- [x] 3.2 Crear `App\Http\Controllers\Documentos\DocumentoProcesoController` con `store(Proceso $proceso, SubirDocumentoProcesoRequest $request)`, `descargar(Proceso $proceso, Documento $documento)` y `destroy(Proceso $proceso, VinculoDocumento $vinculo)`, autorizando los tres con `Gate::authorize` contra el permiso `documentos.gestionar` (descarga: solo requiere `auth`, no el permiso de gestión).
- [x] 3.3 Crear `routes/documentos.php` con las rutas `POST /procesos/{proceso}/documentos`, `GET /procesos/{proceso}/documentos/{documento}/descargar`, `DELETE /procesos/{proceso}/documentos/{vinculo}`, todas bajo `auth`, nombradas `procesos.documentos.*`. Registrar el require en `routes/web.php`.

## 4. Resource

- [x] 4.1 Agregar `'id' => $this->id` a `App\Http\Resources\PagoProveedores\ProcesoResource`.
- [x] 4.2 Agregar `'documentos'` a ese mismo Resource: lista de `vinculosDocumento` activos con `documento.tipoDocumento.nombre`, `documento.versiones.last.nombre_archivo`, `documento.estadoVigente()`, y el `vinculo.id` (necesario para desvincular).

## 5. Frontend

- [x] 5.1 Regenerar rutas Wayfinder (`php artisan wayfinder:generate` o build de Vite) para los nuevos endpoints.
- [x] 5.2 Actualizar `resources/js/types/pago-proveedores.ts` con los campos `id` y `documentos` en el tipo `Proceso`.
- [x] 5.3 En `pago-proveedores/casos/show.tsx` y `adquisiciones/procesos/show.tsx`, agregar una sección "Documentos" con: formulario de subida (input file + select de tipo de documento activo) y lista de documentos vinculados con enlace de descarga y botón "Desvincular".

## 6. Tests

- [x] 6.1 Feature test: subir un documento válido para un proceso crea `Documento` + `VersionDocumento` + `VinculoDocumento` activo.
- [x] 6.2 Feature test: subir un archivo con tipo MIME no permitido o que excede el tamaño máximo es rechazado y no crea ningún registro.
- [x] 6.3 Feature test: usuario sin `documentos.gestionar` no puede subir ni desvincular, y queda auditado en `security_audit_logs`.
- [x] 6.4 Feature test: el detalle de un proceso (`pago-proveedores/casos/show` o `adquisiciones/procesos/show`) incluye los documentos vinculados activos.
- [x] 6.5 Feature test: descargar un documento vinculado responde con el archivo; sin autenticación responde 401/redirect a login.
- [x] 6.6 Feature test: desvincular un documento lo marca `activo = false` sin eliminar el `Documento` ni sus versiones.

## 7. Validación

- [x] 7.1 Ejecutar `composer test` (incluye `lint:check`, `types:check` y la suite Pest).
- [x] 7.2 Ejecutar `npm run lint:check` y `npm run types:check`.
- [x] 7.3 Probar manualmente en el navegador: subir un documento a `ADQ-DEMO-001`, verificar que aparece en la lista, descargarlo, y desvincularlo.
