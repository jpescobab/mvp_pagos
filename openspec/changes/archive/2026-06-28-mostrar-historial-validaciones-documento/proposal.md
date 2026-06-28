## Why

El cambio de validación de documentos activó la creación de eventos en `validaciones_documento` (quién, cuándo, resultado, observación), pero la UI (`ProcesoResource`) solo expone `estado_vigente()` — el resultado de la última validación. Si un documento fue rechazado con una observación específica ("falta firma del representante legal") y luego corregido y vuelto a rechazar o aprobar, esa observación desaparece de la vista por completo: nadie puede ver por qué se rechazó la primera vez, ni quién lo hizo, ni cuándo. Para un sistema cuya razón de ser es la trazabilidad de control documental, perder el historial de motivos de rechazo en la UI es justamente el tipo de gap que el harness pide evitar.

## What Changes

- `ProcesoResource` expone, por cada documento vinculado, su historial completo de `validaciones_documento` (estado, observación, quién validó, cuándo), no solo el estado vigente.
- UI: en la sección "Documentos" de ambas vistas (`pago-proveedores/casos/show.tsx`, `adquisiciones/procesos/show.tsx`), cada documento muestra su historial de validaciones (más reciente primero), con la observación visible cuando existe.

Fuera de alcance: no se cambia ningún comportamiento de creación de validaciones (`ValidacionDocumentoController` no se toca); esto es puramente de lectura/exposición de datos que ya existen.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `documentos-expediente-variable`: se agrega el requisito de que el historial de validaciones de un documento sea consultable, no solo su estado vigente.

## Impact

- `app/Http/Resources/PagoProveedores/ProcesoResource.php`.
- `app/Http/Controllers/PagoProveedores/CasoPagoProveedorController.php` y `app/Http/Controllers/Adquisiciones/ProcesoAdquisicionController.php` (eager load de `documento.validaciones.validadoPor`).
- `resources/js/types/pago-proveedores.ts` y ambos `show.tsx`.
- Tests nuevos en `tests/Feature/Documentos/`.
