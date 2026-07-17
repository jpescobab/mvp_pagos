## Context

`CasoPagoProveedorController::index()` hoy no lee ningún parámetro del `Request`: pagina 20 casos fijos, sin filtro. `CasoPagoProveedor` no tiene columna de estado propia; el estado vive en `Proceso->estadoActual` (`morphOne`), vinculado a `EstadoWorkflow.codigo` del workflow `pago_proveedores` (14 estados, seedeados en `WorkflowPagoProveedoresSeeder`).

El criterio de "listo para aprobar" (checklist con documentos obligatorios aprobados + totales verificados) ya existe y está centralizado en `RevisionEgresoService::pagoListoParaAprobar(CasoPagoProveedor $caso)`, que internamente resuelve la `InstanciaRevision` activa a partir del estado del `Proceso`, llama a `ValidacionDocumentoInstanciaService::todosAprobados()` y a `totalesVerificados()` (que lee `RevisionPagoInstancia.totales_verificados`). Este mismo criterio ya gobierna si el botón "Aprobar" está habilitado en `revision/index.tsx`. El indicador del listado debe reutilizar exactamente este método, no reimplementar la regla.

## Goals / Non-Goals

**Goals:**
- Exponer en el listado, sin request adicional, si cada caso en revisión activa está "listo para aprobar" según la regla ya existente.
- Permitir filtrar el listado por estado de workflow, con un valor por defecto que oculta los estados avanzados/finales.
- Mantener el filtro y el indicador 100% derivados de datos/reglas ya existentes — cero lógica de negocio nueva.

**Non-Goals:**
- No se agrega ninguna transición de workflow nueva ni se automatiza la aprobación. El estado del `Proceso` no cambia por este change.
- No se toca `revision/index.tsx`, `RevisionTotalesController`, `RevisionPagoInstancia` ni `TransicionWorkflowService`.
- No se introduce búsqueda de texto libre (fuera del alcance pedido); solo filtro de estado.

## Decisions

**1. El indicador se calcula en el backend y se expone como campo booleano del `CasoPagoProveedorResource`, no se recalcula en el frontend.**
Alternativa descartada: replicar la lógica en React como se hizo para `preparacion-egreso-card.tsx` (que ya tiene un comentario explícito advirtiendo que esa duplicación es frágil). Para este indicador se evita repetir el patrón: el resource llama a `RevisionEgresoService::pagoListoParaAprobar()` (inyectado o resuelto vía el container) solo cuando el `Proceso` está en `en_revision_finanzas`/`en_revision_zonal`; en cualquier otro estado el campo es `false` sin ejecutar la consulta.

**2. El filtro de estado usa el código de `EstadoWorkflow` (`proceso.estado_actual.codigo`), no un enum duplicado en PHP ni en TS.**
El controlador valida el código recibido contra los estados reales del `DefinicionWorkflow` código `pago_proveedores` (vía `whereHas('estadoActual', fn ($q) => $q->where('codigo', $estado))` cuando se pide un estado puntual). El frontend recibe la lista de estados disponibles (código + nombre) como prop Inertia adicional para poblar el `<select>`, en vez de hardcodear los 14 valores en TS.

**3. Valor por defecto del filtro ("no revisados") se resuelve en el backend, no en el frontend.**
Si el `Request` no trae `estado`, el controlador aplica `whereHas('estadoActual', fn ($q) => $q->whereNotIn('codigo', [...8 estados avanzados/finales]))` en vez de que React decida qué mostrar al montar. Así una carga directa de la URL sin querystring (bookmark, link compartido) se comporta igual que la primera visita. Un valor explícito `estado=todos` en la URL desactiva el filtro.

**4. La lista de "estados avanzados/finales" excluidos por defecto se define como constante, no en la base de datos.**
No amerita una columna nueva (`es_estado_avanzado` en `estados_workflow`) para 8 valores fijos de un solo workflow; una constante es suficiente y más simple que modelarlo en datos.

**5. (Enmienda previa a archivar) La constante y el query de filtrado se mueven de `CasoPagoProveedorController::index()` a un Service nuevo, `ListadoCasoPagoProveedorService::paginar()`.**
La tarea 1.2 original dejaba abierto poner la constante "en el propio controlador o en un método estático de `CasoPagoProveedor`". Una auditoría posterior del módulo (ver change `extraer-logica-negocio-controllers-pago-proveedores`) confirmó que dejarla en el controller, junto con los dos `whereHas` anidados que la aplican, viola la directriz de CLAUDE.md de controladores livianos con lógica de negocio en Services — igual que ya se resolvió para el resto del módulo (`RevisionEgresoService`, `RevisionEgresoPresenter`). Se corrige antes de archivar este change para no dejar un controller con lógica de negocio inline documentado como "correcto" en el spec. Sin cambio de comportamiento: mismo filtro, mismo valor por defecto, mismo `with(...)` de relaciones.

## Risks / Trade-offs

- **[Riesgo] El campo `listo_para_aprobar` ejecuta `pagoListoParaAprobar()` por cada caso de la página (N llamadas), cada una con sus propias queries a `RevisionPagoInstancia`/documentos.** → Mitigación: solo se ejecuta para casos en `en_revision_finanzas`/`en_revision_zonal` (normalmente una fracción de los 20 de la página, y encima el filtro por defecto ya los prioriza); si el profiling tras implementar muestra N+1 relevante, se resuelve con eager loading de las relaciones que usa `ValidacionDocumentoInstanciaService`/`totalesVerificados()` antes de optimizar prematuramente (regla de rendimiento del proyecto: medir antes de optimizar).
- **[Riesgo] Un usuario que aplique el filtro por defecto podría no darse cuenta de que hay casos ocultos en estados avanzados.** → Mitigación: el `<select>` de estado es visible y explícito (no un filtro invisible), con opción "Todos" siempre disponible a un clic.

## Migration Plan

No aplica migración de base de datos (no hay columnas ni tablas nuevas). Es un cambio de código (controller, resource, página React) desplegable directamente; sin pasos de rollback especiales más allá de revertir el commit/PR.
