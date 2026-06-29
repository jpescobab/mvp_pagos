## Context

`SnapshotSgf` (tabla `snapshots_sgf`) guarda `payload_crudo`, `payload_normalizado`, `hash` y `capturado_en`, vinculado a su `ImportacionSgf` (`fuente`, `iniciado_por`, `iniciado_en`). No existe FK directa entre `caso_pago_proveedor` y `snapshots_sgf`: el emparejamiento siempre fue por valor de `sgf_id` (así lo hace `CasoPagoProveedorImporter::importarDesdeSnapshot()`), nunca por relación declarada en el modelo. Por diseño (`pago-proveedores-sgf`/`sgf-origen-snapshot`), `snapshots_sgf` es append-only: cada importación de un mismo `sgf_id` crea una fila nueva, nunca sobrescribe la anterior — por lo tanto el historial completo es información real y distinta de la simple referencia vigente (`sgf_status`) que ya se muestra hoy.

El patrón de "lista expandible con detalle JSON" ya existe en este proyecto (`resources/js/pages/auditoria/index.tsx`, sección 5 del change `visualizar-auditoria-acciones`): cada fila tiene un botón "Ver detalle"/"Ocultar" que muestra un `<pre>` con el JSON completo. Esta tarea reutiliza ese mismo patrón en vez de inventar uno nuevo.

## Goals / Non-Goals

**Goals:**
- Mostrar, en el detalle de un `caso_pago_proveedor`, el historial completo de `snapshots_sgf` que comparten su `sgf_id`, ordenado del más reciente al más antiguo.
- Cada item del historial muestra fecha de captura, hash y fuente de la importación, con un detalle expandible que muestra `payload_crudo` y `payload_normalizado` completos.

**Non-Goals:**
- No se agrega ninguna acción de escritura (crear, editar o eliminar un snapshot) — `snapshots_sgf` solo se crea desde `ImportadorSgf::importarFila()`, fuera de alcance de esta tarea.
- No se crea una página o ruta nueva — el historial se entrega como parte de la respuesta ya existente de `pago-proveedores.casos.show`, igual que el checklist documental o el historial de transiciones.
- No se modela una FK real `snapshot_sgf_id` en `caso_pago_proveedor` — el emparejamiento por valor de `sgf_id` ya es el criterio establecido desde la tarea 7/8 y cambiarlo es un cambio de esquema fuera de alcance.

## Decisions

1. **`CasoPagoProveedor::snapshotsSgf()` como `hasMany(SnapshotSgf::class, 'sgf_id', 'sgf_id')`, ordenado por `id` descendente.** Es una relación Eloquent válida con claves foránea/local explícitas (no requiere una columna `snapshot_sgf_id`): el emparejamiento por `sgf_id` ya es el contrato real entre ambos modelos, esta relación solo lo declara donde antes era una query manual repetida.
2. **Reutilizar el patrón de fila expandible con `<pre>` de `auditoria/index.tsx`** en vez de un modal o una página separada — mismo criterio visual ya validado, sin introducir un componente nuevo solo para esto.
3. **Sin permiso nuevo.** El historial de snapshots es de solo lectura y ya queda detrás de `Gate::authorize('view', $caso)` en `CasoPagoProveedorController::show()` — el mismo permiso con el que ya se ve el resto del detalle del caso (proveedor, checklist, documentos). No hay ninguna acción nueva que autorizar.

## Risks / Trade-offs

- **[Riesgo] `payload_crudo`/`payload_normalizado` pueden ser grandes o contener datos sensibles del proveedor (RUT, monto).** → Mitigación: ya están detrás de `Gate::authorize('view', $caso)`, mismo nivel de exposición que el resto del detalle del caso (que ya muestra RUT y monto del proveedor sin restricción adicional); no se agrega un permiso nuevo porque no es un dato más sensible que el que ya se muestra.
