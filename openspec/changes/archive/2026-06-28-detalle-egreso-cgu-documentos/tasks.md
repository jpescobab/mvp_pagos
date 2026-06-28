## 1. Backend: detalle de egreso CGU

- [x] 1.1 Agregar `EgresoCguController::show(EgresoCgu $egreso)` con `Gate::authorize('view', $egreso)`, cargando `items.caso.proveedor` y `vinculosDocumento.documento.tipoDocumento` y `vinculosDocumento.documento.versiones`.
- [x] 1.2 Agregar ruta `pago-proveedores.egresos-cgu.show` en `routes/pago-proveedores.php`.
- [x] 1.3 Extender `EgresoCguResource` con `id` y un mapeo de `documentos` (vínculo activo, tipo, nombre de archivo, estado vigente) análogo a `ProcesoResource::mapDocumentosVinculados()` (sin historial de validaciones).

## 2. Backend: documentos sobre EgresoCgu

- [x] 2.1 Cambiar la firma de `GestorDocumentoProceso::subirYVincular()` a `Proceso|EgresoCgu $vinculable` (union type); ajustar el cuerpo a usar el nombre de parámetro genérico.
- [x] 2.2 Agregar `EgresoCguPolicy::gestionarDocumentos(User $user, EgresoCgu $egreso): bool` delegando en `$user->can('documentos.gestionar')`.
- [x] 2.3 Crear `App\Http\Controllers\Documentos\DocumentoEgresoCguController` con `store()`, `descargar()`, `destroy()`, reutilizando `SubirDocumentoProcesoRequest` y `GestorDocumentoProceso`.
- [x] 2.4 Agregar rutas `egresos-cgu/{egresoCgu}/documentos` (store, `{documento}/descargar`, `{vinculo}` destroy) en `routes/documentos.php`.

## 3. Frontend

- [x] 3.1 Agregar tipos `EgresoCguDetalle` (o extender `EgresoCgu` con `id`, `documentos`) en `resources/js/types/pago-proveedores.ts`.
- [x] 3.2 Crear `resources/js/pages/pago-proveedores/egresos-cgu/show.tsx`: detalle del egreso, items cubiertos, y bloque de documentos (subir/descargar/desvincular) siguiendo el patrón visual de `casos/show.tsx`.
- [x] 3.3 Enlazar cada fila de `egresos-cgu/index.tsx` a su detalle (`Link` con `egresosCgu.show(egreso.id)`).
- [x] 3.4 Regenerar rutas Wayfinder (`php artisan wayfinder:generate --with-form`).

## 4. Tests y validación

- [x] 4.1 Feature test: detalle de un egreso CGU muestra sus items y documentos vinculados.
- [x] 4.2 Feature test: subir un documento a un egreso CGU crea `Documento`/`VersionDocumento`/`VinculoDocumento`; usuario sin `documentos.gestionar` es bloqueado y queda auditado.
- [x] 4.3 Feature test: descargar y desvincular un documento de un egreso CGU.
- [x] 4.4 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`npm run types:check`.
- [x] 4.5 Verificación manual en navegador: crear/subir un documento de prueba a un egreso CGU y confirmar que aparece, se descarga y se puede desvincular.

## 5. Especificación

- [x] 5.1 Confirmar que las specs delta (`api-pago-proveedores`, `paginas-pago-proveedores`, `documentos-expediente-variable`) quedaron sincronizadas al archivar el change.
