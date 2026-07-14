## Context

`DocumentoProcesoController` (`app/Http/Controllers/Documentos/DocumentoProcesoController.php`) ya agrupa 4 acciones sobre documentos de un `Proceso` (`store`, `nuevaVersion`, `descargar`, `destroy`), todas bajo el mismo `Gate::authorize('gestionarDocumentos', $proceso)`. `GestorDocumentoProceso` (`app/Services/Documentos/GestorDocumentoProceso.php`) es la capa de servicio que ya centraliza esa lógica.

`ImportacionSgfController::show()` (mejorado en un change anterior de esta sesión) ya resuelve un mapa `sgf_id => CasoPagoProveedor` sin N+1 para mostrar proveedor/monto/estado por snapshot; `ImportacionSgfResource::mapSnapshots()`/`resumen()` ya exponen ese detalle. `EgresoCguController::create()` (`app/Http/Controllers/PagoProveedores/EgresoCguController.php`) hoy lista `CasoPagoProveedor::whereDoesntHave('egresoCguItems')` sin ningún filtro adicional; `egresos-cgu/crear.tsx` selecciona con checkboxes en memoria sobre esa lista completa.

## Goals / Non-Goals

**Goals:**
- Permitir corregir la clasificación de un documento ya vinculado (sin volver a subirlo) desde el checklist del caso.
- Mostrar, por caso de una importación SGF, si ya está listo para pasar a Asignar Egreso.
- Preseleccionar en el formulario de creación de Egreso CGU los casos listos de una corrida específica, sin romper el flujo manual existente.

**Non-Goals:**
- No se mejora la heurística de clasificación del conector Playwright (`inferirTipoDocumento`) — sigue siendo responsabilidad de SGF/calibración futura; este change solo da la herramienta para corregirla manualmente caso por caso.
- No se bloquea la creación de un Egreso CGU con casos no listos — sigue siendo posible incluirlos manualmente, el sistema solo no los preselecciona.

## Decisions

**1. Reclasificar documento: nuevo método en `DocumentoProcesoController`, no un controlador nuevo.**
Las 4 acciones existentes ya viven juntas bajo el mismo Gate; sumar `reclasificar(Proceso $proceso, Documento $documento, ReclasificarDocumentoRequest $request)` es más cohesivo que crear un 5º archivo de controlador solo para esto. Verifica que el `Documento` esté vinculado activamente a ese `Proceso` antes de reclasificar (defensa contra IDOR entre procesos). `GestorDocumentoProceso::reclasificar()` hace el `update()`.

**2. El checklist expone qué documentos del caso son "huérfanos" (no coinciden con ningún ítem), calculado en el backend.**
`ProcesoResource::mapDocumentosVinculados()` ya expone cada documento vinculado; se le agrega `tipo_documento_id` y `coincide_checklist: bool` (comparado contra el set de `tipo_documento_id` de los ítems del checklist ya resuelto). Calcularlo en PHP evita duplicar esa comparación en React. En cada ítem pendiente del checklist, un `<Select>` con los documentos huérfanos permite re-vincularlos con un clic — alternativa al atajo de subida ya existente, no lo reemplaza.

**3. Criterio "listo para Asignar Egreso"**: `tipo_proceso_pago_id` clasificado + al menos un `RegistroContableCgu` (Traspaso) + todos los ítems `obligatorio` del checklist con `documento_id !== null` + `proveedor_id !== null`. No exige que los documentos estén *validados* — esa validación ocurre después, en la instancia de revisión de Finanzas; exigirla aquí bloquearía el flujo antes de que exista la etapa que la realiza.

**4. `ImportacionSgfController::show()` re-resuelve el checklist de cada caso de la corrida, no solo lee lo ya calculado.**
Igual que `CasoPagoProveedorController::cargarDetalle()` ya hace por caso individual, para que el indicador `listo_para_egreso` sea preciso sin depender de que alguien haya abierto cada caso manualmente. El volumen es acotado (una corrida SGF, no todo el sistema).

**5. Preselección en `EgresoCguController::create()` vía query param opcional `trabajo_integracion_id`, sin cambiar el comportamiento por defecto.**
Si viene el parámetro, se resuelven los `sgf_id` de esa corrida (`TrabajoIntegracion->snapshotsDatosExternos->pluck('referencia_externa')`) y se aplica `whereIn('sgf_id', $sgfIds)` sobre la lista ya filtrada por `whereDoesntHave('egresoCguItems')`. Sin el parámetro, el comportamiento actual (todos los casos pendientes del sistema) no cambia — cero regresión para quien sigue usando el formulario directo.

**6. Avance parcial, no bloqueo total.** El botón "Continuar a Asignar Egreso" se habilita con ≥1 caso listo; los no listos se muestran igual en el formulario (destildados), permitiendo incluirlos manualmente si el usuario decide que están listos por otra vía. Consistente con que el resto del sistema opera caso por caso, no por lote obligatorio.

## Risks / Trade-offs

- [Riesgo] Recalcular el checklist de cada caso al abrir el detalle de una importación (potencialmente decenas de casos) agrega queries por caso. → Mitigación: mismo patrón ya usado en el detalle de caso individual; el volumen de una corrida SGF es acotado (confirmado en producción: 15-16 casos por corrida), no el total del sistema.
- [Riesgo] Un documento reclasificado podría "robarle" su vínculo a un ítem del checklist que antes lo tenía (si dos ítems comparten indirectamente el mismo tipo tras la reclasificación). → Mitigación: `documentosVinculadosPorTipo()` ya resuelve por `tipo_documento_id` tomando el más reciente; el comportamiento es el mismo que subir un documento nuevo del tipo correcto, ya cubierto por el resolutor existente.

## Migration Plan

1. Sin migración de esquema (no hay tablas ni columnas nuevas).
2. Cambio de backend (nuevo método + rutas) y frontend — deploy normal, sin downtime.
3. Rollback: revertir el commit deja el checklist sin el control de vinculación de huérfanos y la importación sin el indicador "listo" — sin pérdida de datos (los documentos y vínculos ya existentes no se tocan).
