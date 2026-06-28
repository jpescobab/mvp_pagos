## Why

El cambio anterior activó el checklist documental real de Adquisiciones: el detalle de un proceso ahora muestra qué documentos son exigibles según su modalidad (bases, resolución de adjudicación, contrato, garantía, acta de recepción). Pero el modelo documental (`Documento`, `VersionDocumento`, `VinculoDocumento`, `ValidacionDocumento`) no tiene ningún punto de entrada HTTP: no existe forma de subir un archivo y vincularlo a un proceso. El checklist queda permanentemente en "pendiente" sin ninguna acción posible — es evidencia exigida que nadie puede aportar. Esto es infraestructura core ("expediente documental variable" no es un módulo desactivable), por lo que beneficia a Pago de Proveedores y Adquisiciones por igual sin tocar el workflow interno de ninguno.

## What Changes

- Subir un archivo y vincularlo a un `Proceso` (de cualquier módulo, vía `vinculable` polimórfico): crea `Documento` + su primera `VersionDocumento` + un `VinculoDocumento` activo.
- Listar los documentos vinculados (activos) de un proceso, con su tipo, nombre de archivo y estado vigente, en el mismo Resource que ya sirve `checklist` (`ProcesoResource`).
- Descargar un documento vinculado (endpoint protegido, no URL pública directa — son documentos institucionales sensibles).
- Desvincular un documento de un proceso sin borrar su historial (`vinculo_documento.activo = false`), igual que ya exige la spec existente.
- Permiso nuevo `documentos.gestionar` (core, no de un módulo funcional) para subir/desvincular.
- UI mínima en `pago-proveedores/casos/show.tsx` y `adquisiciones/procesos/show.tsx`: input de archivo + selector de tipo de documento, lista de documentos vinculados con botón de descarga y de desvincular.

Fuera de alcance explícito: no se implementa el flujo de validación/rechazo de documentos (`validaciones_documento`) ni la sincronización automática entre documentos subidos y `estado_cumplimiento` del checklist — son features propias, no necesarias para destrabar la subida básica. Tampoco se modifica el workflow interno de ningún módulo.

## Capabilities

### New Capabilities

(ninguna nueva — activa comportamiento ya definido en `documentos-expediente-variable`)

### Modified Capabilities

- `documentos-expediente-variable`: se agrega el requisito de que exista un punto de entrada HTTP real para subir, listar, descargar y desvincular documentos de un proceso (hoy solo existe el modelo de datos, sin wiring).
- `seguridad-auditoria`: se agrega el permiso core `documentos.gestionar`.

## Impact

- Nuevo `App\Services\Documentos\GestorDocumentoProceso` (o similar) para encapsular la lógica de subir/vincular/desvincular.
- Nuevo `App\Http\Controllers\Documentos\DocumentoProcesoController` con rutas anidadas bajo cada módulo (o una ruta genérica por `proceso_id`, a decidir en design).
- `app/Http/Resources/PagoProveedores/ProcesoResource.php`: agregar `documentos` junto a `checklist`.
- Nuevo disco de almacenamiento local privado para los archivos (config/filesystems.php, si no basta el disco `local` por defecto).
- `resources/js/pages/pago-proveedores/casos/show.tsx` y `resources/js/pages/adquisiciones/procesos/show.tsx`: UI de subida/listado/descarga/desvínculo.
- Tests nuevos en `tests/Feature/Documentos/`.
