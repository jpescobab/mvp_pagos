## Why

Los dos cambios anteriores activaron la subida de documentos y su sincronización con el checklist (`pendiente` → `cargado` → `valido`/`rechazado`), pero ese último salto (`cargado` → `valido`/`rechazado`) hoy es imposible de producir: `validaciones_documento` y `Documento::estadoVigente()` ya existen y están testeados de forma aislada, pero no hay ningún endpoint HTTP para crear un evento de validación. Un documento subido queda `cargado` para siempre. Sin esto, la evidencia documental nunca pasa por el control de calidad que el harness exige ("expediente documental" no es solo archivar, es también validar).

## What Changes

- Endpoint para validar o rechazar un documento vinculado a un proceso: crea un `ValidacionDocumento` (`estado`: `valido`|`rechazado`, `observacion` opcional, `validado_por`, `validado_en`).
- Permiso nuevo y dedicado `documentos.validar`, distinto de `documentos.gestionar` (subir/desvincular es una acción distinta de validar/rechazar — separación de funciones, igual que `pago_proveedores.vincular_adquisicion` se separó de los permisos de ciclo de vida de Adquisiciones).
- El checklist ya refleja automáticamente el nuevo estado en su siguiente resolución (`ResolutorChecklistDocumentalProceso` ya lee `Documento::estadoVigente()`, sin cambios necesarios ahí).
- UI: en la sección "Documentos" de ambas vistas (`pago-proveedores/casos/show.tsx`, `adquisiciones/procesos/show.tsx`), botones "Validar" y "Rechazar" (con comentario) por documento vinculado, mostrando el estado vigente actualizado y el historial no se pierde (cada evento queda registrado, nunca se sobreescribe).

Fuera de alcance: no se restringe quién puede validar según el `tipo_documento` o el módulo (el permiso es global, igual que `documentos.gestionar`); no se bloquea ninguna transición de workflow por documentos sin validar (esa lógica ya existe de forma independiente en `ResolutorValidacionDocumental` y no se toca).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `documentos-expediente-variable`: el requisito "Versionar documentos con trazabilidad de validación" pasa de tener su escenario de validación solo a nivel de modelo a tener también un punto de entrada HTTP real.
- `seguridad-auditoria`: se agrega el permiso core `documentos.validar`.

## Impact

- Nuevo `App\Http\Controllers\Documentos\ValidacionDocumentoController`.
- Nuevo Form Request de validación.
- `App\Policies\ProcesoPolicy`: nuevo método `validarDocumentos`.
- `database/seeders/RolesAndPermissionsSeeder.php`.
- `routes/documentos.php`.
- `App\Http\Resources\PagoProveedores\ProcesoResource`: sin cambios necesarios (`estado_vigente` ya se expone).
- UI de ambos `show.tsx` y tipos TS.
- Tests nuevos en `tests/Feature/Documentos/`.
