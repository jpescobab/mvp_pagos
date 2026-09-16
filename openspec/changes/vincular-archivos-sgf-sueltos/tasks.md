## 1. Servicio de detección

- [x] 1.1 Crear `app/Services/Sgf/ArchivosSgfSueltosResolver.php`: método `disponibles(CasoPagoProveedor $caso): array` — `Storage::disk('local')->files("sgf-documentos/{$caso->sgf_id}")` (retorna `[]` si la carpeta no existe, `sgf_id` siempre está presente en el schema) excluyendo las rutas ya presentes en `versiones_documento.ruta_archivo` (consulta `whereIn`); retorna `list<array{ruta_archivo: string, nombre_archivo: string}>`.

## 2. Vincular archivo existente (genérico a Proceso)

- [x] 2.1 Agregar `vincularArchivoExistente(Proceso $proceso, string $rutaArchivo, TipoDocumento $tipoDocumento, User $usuario): VinculoDocumento` a `app/Services/Documentos/GestorDocumentoProceso.php`, dentro de `DB::transaction`: crea `Documento` (`tipo_documento_id`, `titulo` = nombre base del archivo, `subido_por`), crea `VersionDocumento` (`ruta_archivo` = la recibida, `nombre_archivo` = `basename($rutaArchivo)`, `tipo_mime` = `Storage::disk('local')->mimeType($rutaArchivo)`, `tamano_bytes` = `Storage::disk('local')->size($rutaArchivo)`, `hash` = `hash_file('sha256', Storage::disk('local')->path($rutaArchivo))`, `subido_por`), y `$proceso->vinculosDocumento()->create(['documento_id' => ..., 'activo' => true])`.

## 3. Controlador y ruta

- [x] 3.1 Crear `app/Http/Requests/Documentos/VincularArchivoExistenteRequest.php`: `ruta_archivo` (`required`, `string`), `tipo_documento_id` (`required`, `Rule::exists('tipos_documento', 'id')->where('activo', true)`).
- [x] 3.2 Agregar método `vincularExistente(Proceso $proceso, VincularArchivoExistenteRequest $request, ArchivosSgfSueltosResolver $resolver): RedirectResponse` a `app/Http/Controllers/Documentos/DocumentoProcesoController.php`: `Gate::authorize('gestionarDocumentos', $proceso)`; revalida que `$request->string('ruta_archivo')` esté en `$resolver->disponibles($proceso->sujeto)` (si `$proceso->sujeto` no es `CasoPagoProveedor`, el conjunto es vacío) — si no está, `back()->withErrors(['ruta_archivo' => '...'])`; si está, resuelve el `TipoDocumento` y llama `GestorDocumentoProceso::vincularArchivoExistente()`.
- [x] 3.3 Ruta nueva en `routes/documentos.php`: `POST procesos/{proceso}/documentos/vincular-existente` → `procesos.documentos.vincular-existente`.

## 4. Resource / prop al frontend

- [x] 4.1 En `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php::show()`, agregar la prop `archivosSueltosSgf` usando `ArchivosSgfSueltosResolver::disponibles($caso)`.

## 5. Frontend

- [x] 5.1 Agregar el tipo `ArchivoSgfSuelto = { ruta_archivo: string; nombre_archivo: string }` en `resources/js/types/pago-proveedores.ts`, y la prop `archivosSueltosSgf: ArchivoSgfSuelto[]` al `PageProps` de `resources/js/pages/pago-proveedores/casos/show.tsx`.
- [x] 5.2 En `casos/show.tsx`, agregar `vincularArchivoSuelto(tipoDocumentoId: number, rutaArchivo: string)` que hace `router.post(documentos['vincular-existente']({proceso: caso.proceso.id}).url, {ruta_archivo: rutaArchivo, tipo_documento_id: tipoDocumentoId}, {...})` (mismo patrón que `vincularHuerfano`); extender el handler de "Vincular" para elegir esta rama cuando el valor seleccionado sea una ruta de `archivosSueltosSgf` (en vez de un id numérico de documento).
- [x] 5.3 En `resources/js/components/pago-proveedores/checklist-documental-card.tsx`, agregar prop `archivosSueltos: ArchivoSgfSuelto[]`, incluir sus opciones en el `Select` existente ("vincula uno ya importado") con `value={archivo.ruta_archivo}` y label = `archivo.nombre_archivo` + indicador "(archivo suelto)"; ajustar `puedeVincularExistente` para considerar también `archivosSueltos.length`.
- [x] 5.4 Regenerar Wayfinder (`php artisan wayfinder:generate --with-form`).

## 6. Tests

- [x] 6.1 `tests/Feature/Sgf/ArchivosSgfSueltosResolverTest.php`: un archivo físico sin `VersionDocumento` aparece en `disponibles()`; un archivo con `VersionDocumento` ya registrado no aparece; un caso cuya carpeta no existe todavía devuelve `[]` sin error.
- [x] 6.2 `tests/Feature/Documentos/VincularArchivoExistenteTest.php`: vinculación exitosa crea `Documento`/`VersionDocumento`/`VinculoDocumento` con el hash del archivo real; rechazo cuando la ruta no está en el conjunto vigente (archivo ya vinculado o inexistente); rechazo sin el permiso `documentos.gestionar`.
- [x] 6.3 Confirmar en un test que vincular un archivo suelto no dispara ninguna transición de workflow sobre el `Proceso` (mismo criterio que `VinculoContratoTest.php`/`DetalleFacturaSinEfectoWorkflowTest.php`).

## 7. Validación final

- [x] 7.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados.
- [x] 7.2 `composer test` (lint:check + types:check + suite Pest completa).
- [x] 7.3 `npm run lint:check` y `npm run types:check` sobre el frontend tocado.
- [ ] 7.4 Verificación manual: crear un archivo de prueba dentro de `storage/app/private/sgf-documentos/{sgfId}/` de un caso real sin registrar como `Documento`, confirmar que aparece en el selector del checklist, vincularlo, y confirmar que aparece como documento del ítem sin alterar el estado del caso.
