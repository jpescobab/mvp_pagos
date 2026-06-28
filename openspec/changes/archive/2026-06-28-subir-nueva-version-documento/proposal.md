## Why

El cambio `subir-vincular-documentos-proceso` dejó explícitamente como Non-Goal: "no se permite subir nuevas versiones de un documento existente — cada subida crea un Documento nuevo". El modelo ya soporta versionado (`versiones_documento`, probado a nivel de modelo en `VersionamientoDocumentoTest`), pero no existe ningún punto de entrada HTTP para corregir un documento ya subido sin perder su identidad (su `tipo_documento`, sus vínculos a procesos, y crucialmente su historial de validaciones). Hoy, la única forma de "corregir" un PDF mal subido es desvincularlo y subir uno nuevo, lo que rompe la trazabilidad de validación de ese documento (un documento `rechazado` con una corrección sube como un `Documento` completamente nuevo, sin relación con el rechazo anterior).

## What Changes

- Endpoint para subir una nueva versión de un `Documento` ya existente: crea una `VersionDocumento` con el siguiente `numero_version`, sin tocar `Documento` ni sus vínculos ni su historial de validaciones.
- UI: en la sección "Documentos" de ambas vistas, una acción "Nueva versión" por documento vinculado (input de archivo), distinta de "Subir" (que sigue creando un documento nuevo para un tipo no vinculado aún).
- El checklist sigue funcionando sin cambios: sigue resolviendo por `tipo_documento_id` y el `Documento::estadoVigente()` (que ya lee solo `validaciones_documento`, no versiones) no se ve afectado por agregar una versión.

Fuera de alcance: no se resetea automáticamente el estado de validación al subir una nueva versión (sigue siendo el mismo `Documento`, con el mismo historial de validaciones; si estaba `rechazado`, sigue `rechazado` hasta una nueva validación explícita — comportamiento intencional, no un bug).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `documentos-expediente-variable`: el escenario "Subir una nueva versión de un documento" pasa de estar probado solo a nivel de modelo a tener un punto de entrada HTTP real.

## Impact

- `App\Services\Documentos\GestorDocumentoProceso`: nuevo método `subirNuevaVersion`.
- `App\Http\Controllers\Documentos\DocumentoProcesoController`: nuevo método o controlador dedicado.
- Form Request nuevo.
- `routes/documentos.php`.
- UI de ambos `show.tsx`.
- Tests nuevos en `tests/Feature/Documentos/`.
