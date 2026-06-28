## Why

El cambio anterior permitió subir y vincular documentos a un proceso, pero dejó explícitamente fuera de alcance (como limitación conocida) la sincronización entre esos documentos y el checklist documental: hoy, aunque se suba el PDF exacto que el checklist exige (mismo `tipo_documento_id`), el item sigue mostrando "pendiente" para siempre, porque `ResolutorChecklistDocumentalProceso::resolve()` siempre escribe `estado_cumplimiento: 'pendiente'` sin mirar si ya existe un documento vinculado. El propio modelo `ChecklistDocumentalProcesoItem` ya tiene una columna `documento_id` (nullable, `belongsTo`) pensada exactamente para esto, sin usar. El checklist documental pierde su utilidad como evidencia de control si nunca refleja lo que realmente se subió.

## What Changes

- `ResolutorChecklistDocumentalProceso::resolve()` ahora busca, para cada requisito, el documento vinculado activo más reciente del proceso cuyo `tipo_documento_id` coincida, y completa `documento_id` y `estado_cumplimiento` del item según el estado vigente de ese documento (`Documento::estadoVigente()`).
- Nuevo valor de `estado_cumplimiento`: `cargado` (documento subido, todavía sin pasar por un evento de validación) — sin esto, un documento recién subido sería indistinguible de un requisito sin ningún documento.
- Si el documento vinculado ya tiene un evento de validación, `estado_cumplimiento` refleja ese resultado (`valido` o `rechazado`) en vez de `cargado`.
- El checklist expuesto en `ProcesoResource` incluye el `documento_id` del item (cuando existe) para que el frontend pueda enlazar directamente a su descarga.
- Sin cambios de esquema: la columna `documento_id` ya existe desde la tarea 06.

Fuera de alcance: no se implementa el flujo de validación/rechazo de documentos (sigue sin existir un endpoint para crear `validaciones_documento`); este cambio solo consume el estado que ya exista, no lo crea.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `documentos-expediente-variable`: el escenario "Generar checklist documental" se actualiza para reflejar que cada item se vincula al documento real ya subido, en vez de quedar siempre como un requisito vacío.

## Impact

- `app/Services/Documentos/ResolutorChecklistDocumentalProceso.php`.
- `app/Http/Resources/PagoProveedores/ProcesoResource.php` (agregar `documento_id` al item del checklist).
- `resources/js/types/pago-proveedores.ts` y los `show.tsx` de ambos módulos (mostrar enlace de descarga directo desde el checklist cuando `documento_id` no es null).
- Tests nuevos/actualizados en `tests/Feature/Documentos/`.
