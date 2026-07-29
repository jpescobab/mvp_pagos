## Why

Desde una orden de compra o licitación de Mercado Público se puede vincular a un proceso de adquisición (el vínculo escribe `proceso_adquisicion_id`), pero el detalle del proceso **nunca muestra esas compras**: `ProcesoAdquisicionController::show` no las carga, `ProcesoAdquisicionResource` no las expone y la pantalla no tiene sección para ellas. El vínculo es de una sola vía — entra el dato pero el expediente del proceso no lo refleja. Además el modelo `ProcesoAdquisicion` tiene `ordenesCompraMercadoPublico()` pero le falta la relación inversa `licitacionesMercadoPublico()`, pese a que la licitación sí apunta al proceso. Resultado: la evidencia central de la compra (OC y licitación adjudicada) queda invisible en el proceso.

## What Changes

- Se agrega al modelo `ProcesoAdquisicion` la relación `licitacionesMercadoPublico()` (HasMany), simétrica con `ordenesCompraMercadoPublico()`.
- `ProcesoAdquisicionController::show` carga las órdenes de compra y licitaciones de Mercado Público vinculadas.
- `ProcesoAdquisicionResource` expone ambas colecciones con sus campos clave (código, organismo, estado en Mercado Público, monto) y su id para enlazar al detalle.
- El detalle del proceso (`adquisiciones/procesos/show.tsx`) suma dos secciones: **Órdenes de compra (Mercado Público)** y **Licitaciones (Mercado Público)**, cada una listando las vinculadas con enlace a su detalle, o un vacío explícito.
- Tipos TS y tests.

## Capabilities

### New Capabilities
<!-- Ninguna. -->

### Modified Capabilities
- `adquisiciones`: el proceso de adquisición pasa a exponer, además de sus casos de pago, las órdenes de compra y licitaciones de Mercado Público vinculadas.
- `paginas-adquisiciones`: el detalle del proceso muestra las órdenes de compra y licitaciones de Mercado Público vinculadas.

## Impact

- **Modelo**: `app/Models/ProcesoAdquisicion.php` (nueva relación `licitacionesMercadoPublico`).
- **Backend**: `ProcesoAdquisicionController::show` (carga), `app/Http/Resources/Adquisiciones/ProcesoAdquisicionResource.php` (exposición, espejando el patrón de `casos_pago_proveedor`).
- **Frontend**: `resources/js/pages/adquisiciones/procesos/show.tsx`, tipos en `resources/js/types/adquisiciones.ts`.
- **Sin migraciones**: las columnas `proceso_adquisicion_id` y las tablas ya existen; solo se lee lo que ya se escribe.
- **Sin permisos nuevos**, sin tocar el workflow ni el mecanismo de vinculación (que sigue haciéndose desde el lado de Mercado Público).
- **Alcance deliberadamente acotado**: el usuario redefinirá el proceso de compras más adelante; este change solo cierra la fuga de evidencia, no rediseña el flujo.
