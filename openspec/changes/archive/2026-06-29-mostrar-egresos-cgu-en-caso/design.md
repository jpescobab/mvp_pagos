## Context

`egresos_cgu_items` ya tiene la FK real `caso_pago_proveedor_id` (`unique(['egreso_cgu_id', 'caso_pago_proveedor_id'])`), pero `CasoPagoProveedor` nunca declaró la relación inversa — solo `EgresoCgu::items()` existe. `egresos-cgu/show.tsx` (construido en el change `detalle-egreso-cgu-documentos`) ya es una página completa con documentos vinculados; lo único que falta es el enlace desde el lado del caso.

## Goals / Non-Goals

**Goals:**
- Mostrar, en el detalle de un `caso_pago_proveedor`, todos los `egreso_cgu` que lo cubren (vía `egresos_cgu_items`), con número, fecha y el monto específico del item.
- Cada egreso mostrado enlaza a `pago-proveedores.egresos-cgu.show`, ya construido.

**Non-Goals:**
- No se agrega la acción de crear/editar un egreso desde el detalle del caso — esa acción ya existe en `egresos-cgu/crear` y queda fuera de alcance.
- No se restringe a un único egreso por caso: el esquema permite (aunque no es el caso de uso típico) que un caso aparezca en más de un `egreso_cgu`; se muestran todos, igual que el historial completo de snapshots y de registros CGU/bancarios ya expuesto en cambios anteriores.

## Decisions

1. **`CasoPagoProveedor::egresoCguItems(): HasMany` hacia `EgresoCguItem`** (no directamente hacia `EgresoCgu`), porque la FK real está en `egresos_cgu_items.caso_pago_proveedor_id`, y cada item ya tiene el monto específico que ese egreso cubre para ese caso — dato que se pierde si se relacionara directo a `EgresoCgu`.
2. **El link al egreso reutiliza la ruta `pago-proveedores.egresos-cgu.show` ya existente**, sin duplicar lógica de autorización: al hacer clic, la página de destino vuelve a evaluar `Gate::authorize('view', $egresoCgu)` por sí misma.

## Risks / Trade-offs

- **[Riesgo] Ninguno relevante** — es una exposición de solo lectura sobre una FK ya existente, sin tocar workflow, permisos ni escritura.
