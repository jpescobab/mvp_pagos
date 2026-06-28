## 1. Permiso y autorización

- [x] 1.1 Agregar el permiso `documentos.validar` en `RolesAndPermissionsSeeder`, asignado a `superadmin` y `admin`.
- [x] 1.2 Agregar `ProcesoPolicy::validarDocumentos(User $user, Proceso $proceso): bool` (`$user->can('documentos.validar')`).

## 2. Backend HTTP

- [x] 2.1 Crear Form Request `ValidarDocumentoRequest` (`estado`: `required|in:valido,rechazado`; `observacion`: `nullable|string|required_if:estado,rechazado`).
- [x] 2.2 Crear `App\Http\Controllers\Documentos\ValidacionDocumentoController` con `store(Proceso $proceso, Documento $documento, ValidarDocumentoRequest $request)`, autorizando con `Gate::authorize('validarDocumentos', $proceso)`, creando el `ValidacionDocumento` con `validado_por` y `validado_en`.
- [x] 2.3 Agregar la ruta `POST /procesos/{proceso}/documentos/{documento}/validaciones` en `routes/documentos.php`, nombrada `procesos.documentos.validaciones.store`.

## 3. Frontend

- [x] 3.1 Regenerar rutas Wayfinder.
- [x] 3.2 En `pago-proveedores/casos/show.tsx` y `adquisiciones/procesos/show.tsx`, agregar botones "Validar" y "Rechazar" (con diálogo de observación) por documento listado en la sección "Documentos", refrescando el `estado_vigente` mostrado tras la acción.

## 4. Tests

- [x] 4.1 Feature test: validar un documento con el permiso requerido crea el evento y `estadoVigente()` pasa a `valido`.
- [x] 4.2 Feature test: rechazar un documento sin observación es rechazado por validación y no crea ningún evento.
- [x] 4.3 Feature test: rechazar un documento con observación crea el evento y `estadoVigente()` pasa a `rechazado`.
- [x] 4.4 Feature test: usuario sin `documentos.validar` no puede validar ni rechazar, y queda auditado en `security_audit_logs`.
- [x] 4.5 Feature test: tras validar un documento, la siguiente resolución del checklist del proceso refleja el nuevo `estado_cumplimiento`.

## 5. Validación

- [x] 5.1 Ejecutar `composer test`.
- [x] 5.2 Ejecutar `npm run lint:check` y `npm run types:check`.
- [x] 5.3 Probar manualmente en el navegador: validar el documento "Contrato" subido a `ADQ-DEMO-001` y verificar que el checklist pasa de `cargado` a `valido`.
