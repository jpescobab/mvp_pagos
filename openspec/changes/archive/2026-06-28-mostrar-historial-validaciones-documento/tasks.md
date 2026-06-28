## 1. Backend

- [x] 1.1 Agregar `validaciones` al mapeo de `documentos` en `App\Http\Resources\PagoProveedores\ProcesoResource`: lista de `{estado, observacion, validado_por (nombre), validado_en}` ordenada por `id` descendente.
- [x] 1.2 Extender el eager loading en `CasoPagoProveedorController::show()` y `ProcesoAdquisicionController::show()` con `vinculosDocumento.documento.validaciones.validadoPor`.

## 2. Frontend

- [x] 2.1 Agregar el tipo `ValidacionDocumentoHistorial` y el campo `validaciones` en `DocumentoVinculado` (`resources/js/types/pago-proveedores.ts`).
- [x] 2.2 En `pago-proveedores/casos/show.tsx` y `adquisiciones/procesos/show.tsx`, mostrar el historial de validaciones de cada documento (resultado, observación si existe, usuario, fecha), debajo de sus acciones.

## 3. Tests

- [x] 3.1 Feature test: el detalle de un proceso incluye el historial completo de validaciones de un documento, no solo la más reciente.
- [x] 3.2 Feature test: la observación de un rechazo pasado sigue presente en el historial después de una validación posterior.

## 4. Validación

- [x] 4.1 Ejecutar `composer test`.
- [x] 4.2 Ejecutar `npm run lint:check` y `npm run types:check`.
- [x] 4.3 Probar manualmente en el navegador: ver el historial de validaciones del documento "Contrato" en `ADQ-DEMO-001` (ya tiene un evento `valido` y uno `rechazado` de pruebas anteriores) y confirmar que ambos eventos son visibles con su observación.
