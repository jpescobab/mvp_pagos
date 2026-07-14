## Why

En el detalle de un caso de Pago de Proveedores, el bloque "Checklist documental" (`show.tsx:643-688`) es puramente de lectura: lista los tipos de documento pendientes pero no ofrece ninguna acción. Para subir un documento faltante, el usuario con `documentos.gestionar` debe bajar hasta la sección "Documentos" (más abajo en la misma página) y seleccionar manualmente el tipo desde un `<Select>` genérico, sin relación visual con qué ítem del checklist está pendiente — fricción innecesaria en el flujo más frecuente de quien prepara un caso (rol `administrativo_finanzas`).

Además, la página incluye contenido que no aporta a la operación diaria: la sección "Historial de snapshots SGF" expone un volcado JSON crudo (`payload_crudo`/`payload_normalizado`) pensado para depuración, y el texto plano de `sgf_status` junto al `EstadoBadge` duplica información sin agregar valor accionable (el estado que gobierna el caso es el del workflow interno, no el de SGF).

## What Changes

- Cada ítem del checklist documental sin documento vinculado (`estado_cumplimiento: "pendiente"`) SHALL exponer un acceso directo de subida cuando el usuario tenga `documentos.gestionar`: selecciona el archivo y sube inmediatamente ese documento con el tipo correcto, sin bajar a la sección "Documentos" ni seleccionar el tipo manualmente.
- `ProcesoResource` (backend) expone `tipo_documento_id` en cada ítem del checklist, necesario para que el frontend sepa a qué tipo subir sin adivinar por nombre.
- Se elimina la sección "Historial de snapshots SGF" (listado + volcado JSON crudo) de la página de detalle del caso. El botón de acción "Verificar en SGF" se conserva (sigue siendo accionable). El dato **no se borra** de la base — `snapshots_datos_externos` sigue existiendo íntegro para trazabilidad, solo deja de mostrarse en esta pantalla operativa.
- Se elimina el texto plano de `sgf_status` junto al `EstadoBadge`; el estado del workflow interno queda como única fuente de verdad visible en esta pantalla.

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `paginas-pago-proveedores`: el requirement "Página de detalle de un caso con acciones de workflow" se amplía para incluir el acceso directo de subida desde el checklist, y se acota explícitamente qué contenido de origen SGF se muestra (sin volcado crudo ni estado SGF duplicado).

## Impact

- `app/Http/Resources/PagoProveedores/ProcesoResource.php` (agregar `tipo_documento_id` al ítem del checklist)
- `resources/js/types/pago-proveedores.ts` (`ChecklistItem`)
- `resources/js/pages/pago-proveedores/casos/show.tsx` (acceso directo de subida en el checklist; eliminar sección de historial de snapshots SGF y el texto de `sgf_status`)
- Tests: `tests/Feature/PagoProveedores/*` (cubrir `tipo_documento_id` en el resource; ajustar/agregar tests Inertia del detalle del caso si existen assertions sobre las secciones eliminadas)
