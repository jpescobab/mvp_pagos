## Why

El detalle de una corrida de importación SGF (`sgf/importaciones/show.tsx`) hoy solo lista, por cada snapshot producido, su `referencia_externa` (sgf_id), un hash truncado y la fecha de captura. Toda la información realmente útil para un usuario de negocio — proveedor, monto, estado SGF, folio de egreso, período, si el caso ya quedó importado a `casos_pago_proveedor` — ya se captura en `payload_normalizado` del snapshot (vía `NormalizadorSgf`) y en el `caso_pago_proveedor` resultante, pero no se expone en esta pantalla. El usuario necesita poder evaluar el resultado de una importación (cuánto dinero movió, qué proveedores, si algo quedó sin RUT identificado) sin tener que consultar la base de datos directamente.

## What Changes

- `ImportacionSgfResource` (backend) expone por cada snapshot los campos normalizados relevantes: proveedor (nombre si el RUT coincide con un `Proveedor` existente, si no el RUT tal cual), monto, estado SGF, folio de egreso, número, período, fecha SII y observaciones.
- Cada snapshot se enlaza (cuando corresponde) al `caso_pago_proveedor` que produjo, exponiendo su id y el estado actual de su workflow interno, para poder navegar al detalle del caso desde esta pantalla.
- Se agrega un resumen agregado a nivel de la corrida completa: monto total importado y cantidad de proveedores identificados vs. no identificados (RUT sin `Proveedor` coincidente) — señal de calidad de datos útil para quien importó.
- El frontend reemplaza la lista plana actual por un resumen con esa información financiera y de identificación, más una lista de snapshots enriquecida (proveedor, monto, estado, folio/número/período), siguiendo los tokens visuales existentes (`EstadoBadge`, `formatMonto`, `formatNumero`).

## Capabilities

### New Capabilities

(ninguna)

### Modified Capabilities

- `consulta-importaciones-sgf`: el requirement "Ver el detalle de una corrida de importación SGF" se amplía para incluir datos normalizados por snapshot, el vínculo al caso de pago resultante y un resumen financiero agregado de la corrida.

## Impact

- `app/Http/Resources/Sgf/ImportacionSgfResource.php`
- `app/Http/Controllers/Sgf/ImportacionSgfController.php` (carga adicional de `casos_pago_proveedor` relacionados)
- `resources/js/types/sgf.ts`
- `resources/js/pages/sgf/importaciones/show.tsx`
- Tests: `tests/Feature/Sgf/*` (cubrir el detalle enriquecido; verificar existente cubriendo el listado/detalle actual)
