## Why

`EgresoCgu` ya tiene la relación `vinculosDocumento()` (polimórfica, lista para usar) y su `EgresoCguPolicy::view()` ya existe, pero ninguna ruta los invoca: no hay página de detalle de un egreso CGU. El propio `design.md` de `paginas-pago-proveedores` documentó esto como Non-Goal explícito ("No se construye una página de detalle de egreso CGU — no existe esa ruta en `api-pago-proveedores`"). Sin detalle, un egreso CGU no puede llevar adjunto su comprobante de transferencia ni evidencia de pago, pese a que el modelo documental ya está diseñado para vincularse a "cualquier entidad de negocio" — hoy solo `Proceso` lo usa en la práctica.

## What Changes

- Agregar `EgresoCguController::show()` + ruta `pago-proveedores.egresos-cgu.show`.
- Extender `EgresoCguResource` con `id` y el detalle de `items` (sgf_id, proveedor, monto) ya existente, sin romper su uso actual en el listado.
- Generalizar `GestorDocumentoProceso::subirYVincular()` para aceptar `Proceso|EgresoCgu` (ambos exponen `vinculosDocumento()`), evitando duplicar la lógica de subida/creación de versión/vínculo.
- Agregar rutas `egresos-cgu/{egresoCgu}/documentos` (subir, descargar, desvincular) vía un controlador nuevo y delgado que reutiliza el mismo `GestorDocumentoProceso`.
- Agregar `EgresoCguPolicy::gestionarDocumentos()` (delega en el permiso `documentos.gestionar`, igual que `ProcesoPolicy`) para mantener el patrón de autorización por Policy que sí dispara `Gate::after`/auditoría.
- Página `egresos-cgu/show.tsx`: detalle del egreso, casos cubiertos, y lista de documentos vinculados con subir/descargar/desvincular (mismo patrón visual que `casos/show.tsx`).
- Filas del listado (`egresos-cgu/index.tsx`) ahora enlazan a su detalle.

No incluye en este change: validar/rechazar ni versionar documentos de un egreso CGU (se hizo igual de incremental para `Proceso`: subir/vincular primero, validar y versionar en changes posteriores si se necesitan).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `api-pago-proveedores`: agrega "Ver detalle de un egreso CGU vía HTTP".
- `paginas-pago-proveedores`: agrega "Página de detalle de egreso CGU"; el listado ahora enlaza a ella.
- `documentos-expediente-variable`: generaliza "Subir y vincular un documento a un proceso vía HTTP", "Listar y descargar los documentos vinculados a un proceso" y "Desvincular un documento sin perder su historial" para cubrir también `EgresoCgu`, no solo `Proceso`.

## Impact

- Backend: `EgresoCguController`, nuevo `DocumentoEgresoCguController`, `GestorDocumentoProceso` (firma de `subirYVincular`), `EgresoCguPolicy`, `EgresoCguResource`, `routes/pago-proveedores.php`, `routes/documentos.php`.
- Frontend: `egresos-cgu/show.tsx` (nueva), `egresos-cgu/index.tsx` (enlace a detalle), tipos en `resources/js/types/pago-proveedores.ts`.
- Tests: feature tests para detalle de egreso, subida/descarga/desvinculación de documentos sobre `EgresoCgu`, y autorización.
