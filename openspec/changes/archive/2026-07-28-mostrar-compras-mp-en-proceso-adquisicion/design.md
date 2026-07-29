## Context

`OrdenCompraMercadoPublico` y `LicitacionMercadoPublico` tienen `proceso_adquisicion_id` (fillable) y su `belongsTo(ProcesoAdquisicion)`. El vínculo se crea desde el lado de Mercado Público (`Vinculo…Controller::store` hace `update(['proceso_adquisicion_id' => …])`). `ProcesoAdquisicion` tiene `ordenesCompraMercadoPublico()` pero NO `licitacionesMercadoPublico()`. El `show()` del proceso ya carga y expone `casos_pago_proveedor` con el patrón `whenLoaded(...)->map(...)`; se replica ese patrón para las compras.

## Goals / Non-Goals

**Goals:**
- Cerrar la fuga: que el detalle del proceso muestre sus OC y licitaciones de Mercado Público vinculadas, con enlace a su detalle.
- Corregir la asimetría del modelo agregando `licitacionesMercadoPublico()`.

**Non-Goals:**
- No se agrega la capacidad de vincular/desvincular desde el proceso (sigue siendo desde el lado de Mercado Público) — queda para cuando el usuario redefina el proceso de compras.
- No se rediseña el workflow ni el modelo de compras.
- Sin migraciones, sin permisos nuevos.

## Decisions

**1. Relación inversa `licitacionesMercadoPublico()` en el modelo.** HasMany simétrico con `ordenesCompraMercadoPublico()`, para poder cargar y exponer las licitaciones del proceso.

**2. Exposición en el Resource espejando `casos_pago_proveedor`.** Dos claves nuevas (`ordenes_compra_mercado_publico`, `licitaciones_mercado_publico`) con `whenLoaded(...)->map(...)`, incluyendo `id`, `codigo`, `estado_mercado_publico`, `organismo_comprador` (y `monto_total`/`monto_estimado` respectivo) — lo mínimo para listar y enlazar. Solo lectura de lo ya persistido.

**3. Carga en `show()`.** Se agregan `ordenesCompraMercadoPublico` y `licitacionesMercadoPublico` al `load()` del proceso, junto a los eager loads existentes.

**4. UI: dos secciones nuevas en el detalle.** Siguiendo el estilo de las secciones existentes (borde, título, lista o vacío). Cada ítem enlaza al detalle de la OC/licitación vía los helpers de ruta Wayfinder existentes (`ordenes_compra_mp.show`, `licitaciones_mp.show`). `organismo_comprador` es un array (JSON) — se muestra su nombre si viene, con fallback.

## Risks / Trade-offs

- **`organismo_comprador` es un array (cast JSON).** Se accede a su nombre de forma defensiva (fallback `"—"`), sin asumir estructura fija, para no romper si el payload de Mercado Público varía.
- **Solo muestra, no gestiona.** El usuario podría esperar vincular desde el proceso; se deja explícito como no-goal alineado con la futura redefinición.
- **Sin migraciones**: cero riesgo de esquema; el cambio es de lectura/presentación.
