## Context

La Revisión de Pagos (Finanzas y Zonal) se alimenta de `RevisionEgresoPresenter`, que por cada caso llama a `ValidacionDocumentoInstanciaService::documentosDelCaso()`. Ese método devuelve **todos** los documentos activos vinculados al `Proceso` (vía `vinculos_documento` con `activo = true`), sin distinguir los exigidos por el checklist de los documentos extra importados desde SGF. El gating de aprobación se apoya en `ValidacionDocumentoInstanciaService::todosAprobados()` y en `RevisionEgresoService::pagoListoParaAprobar()`, ambos evaluando el estado de **todos** esos documentos.

El checklist documental ya existe y ya distingue obligatorio de opcional: `ChecklistDocumentalProceso` (uno por `Proceso`) tiene N `ChecklistDocumentalProcesoItem`, cada uno con `tipo_documento_id`, `tipo_requisito` (`obligatorio`/`opcional`), `documento_id` (el vinculado, si lo hay) y `estado_cumplimiento`. Lo genera `ResolutorChecklistDocumentalProceso` según workflow/modalidad/monto/estado. Este change **no** cambia cómo se genera el checklist; solo lo usa como fuente de la clasificación en la revisión.

Restricciones del harness relevantes: controladores livianos (el cruce va en Service), React solo renderiza lo que entrega el backend ("expediente documental variable"), la validación por instancia (finanzas/zonal) se mantiene independiente, y todo cambio de estado sigue por `TransicionWorkflowService` (no se toca en este change).

## Goals / Non-Goals

**Goals:**
- Clasificar los documentos de un pago en la revisión como **obligatorio**, **opcional** o **faltante**, calculado en el backend a partir del checklist del proceso.
- Presentar obligatorios (incluidas filas faltantes) antes que opcionales, todos consultables; opcionales aprobables pero no bloqueantes.
- Que "docs OK", `pagoListoParaAprobar` y `todosAprobados` cuenten **solo** obligatorios (aprobados en la instancia activa) y bloqueen si hay algún obligatorio faltante.
- Comportamiento idéntico en Finanzas y Zonal (comparten el mismo Presenter/Services).

**Non-Goals:**
- No se modifica la generación del checklist (`ResolutorChecklistDocumentalProceso`) ni el esquema de datos (sin migraciones).
- No se cambia la máquina de estados ni `TransicionWorkflowService`.
- No se toca la verificación de totales (sigue siendo condición aparte para aprobar).
- No se cambia la validación por instancia ni el historial de validaciones.
- No se altera el checklist del detalle de caso (`show.tsx`); este change es solo la pantalla de Revisión de Pagos.

## Decisions

### 1. La clasificación se calcula en un solo lugar del backend, sobre el checklist ya generado
`ValidacionDocumentoInstanciaService::documentosDelCaso()` deja de devolver una `Collection<Documento>` plana y pasa a devolver una estructura clasificada por caso: obligatorios presentes, faltantes (ítems obligatorios del checklist sin `documento_id`) y opcionales (documentos vinculados cuyo `tipo_documento_id` no está entre los obligatorios del checklist).

- **Fuente de obligatoriedad**: el conjunto de `tipo_documento_id` de los `ChecklistDocumentalProcesoItem` con `tipo_requisito = 'obligatorio'` del `ChecklistDocumentalProceso` del proceso. Es una decisión por **tipo de documento**, no por documento individual (coherente con cómo el checklist define requisitos).
- **Faltante**: ítem obligatorio del checklist cuyo `documento_id` es `null` (no hay documento vinculado de ese tipo). Se representa como placeholder con el `tipo_documento` esperado, sin `Documento` real.
- **Opcional**: documento vinculado activo cuyo `tipo_documento_id` no pertenece al conjunto obligatorio (incluye tipos que el checklist marcó como `opcional` y los "Otro Documento" que no están en el checklist).

Alternativa descartada: calcular la clasificación en `RevisionEgresoPresenter` recorriendo checklist y documentos por separado. Se descarta para no duplicar el cruce (también lo necesita `todosAprobados`) y mantener una única fuente de verdad de "qué es obligatorio para este caso".

Alternativa descartada: hacerlo en el controlador o en React. Viola controladores livianos y "los requisitos los entrega el backend".

### 2. Un método de dominio reutilizable para "obligatorios de este caso"
Se introduce en `ValidacionDocumentoInstanciaService` (o servicio de checklist consumido por él) un helper privado que, dado el `Proceso`, retorna el conjunto de `tipo_documento_id` obligatorios y los ítems obligatorios faltantes. `documentosDelCaso()`, `todosAprobados()` y el gating lo consumen para no recalcular. Se mantiene el nombre del método público `documentosDelCaso()` pero cambia su tipo de retorno; se ajustan todos sus llamadores (`RevisionEgresoPresenter::pago()`, `todosAprobados()`).

### 3. Gating: obligatorios aprobados + sin faltantes
`todosAprobados(caso, instancia)` pasa a: (a) no hay ningún ítem obligatorio faltante, **y** (b) todo documento obligatorio presente tiene `estadoVigente(instancia) = 'valido'`. Si no hay obligatorios definidos por el checklist, se conserva el criterio actual de "no aprobable con lista vacía" (un pago sin obligatorios definidos no se marca listo por documentos — se mantiene el comportamiento defensivo actual de `todosAprobados` que retorna `false` con colección vacía, evaluado ahora sobre obligatorios). Esta sutileza se cubre con test.

`RevisionEgresoService::pagoListoParaAprobar()` sigue combinando "documentos ok" (ahora solo obligatorios) con "totales verificados", sin cambios en la parte de totales.

### 4. Forma del payload hacia React
`RevisionEgresoPresenter::pago()` entrega `documentos` como hoy (para no romper el visor/acciones existentes), pero cada documento gana un campo `clasificacion: 'obligatorio' | 'opcional'`, y se agrega una lista separada (o filas) para los **faltantes** con forma `{ tipo_documento, tipo_documento_id, clasificacion: 'faltante' }` sin `id`. Se agregan además contadores derivados del backend (`obligatorios_ok`, `obligatorios_total`) para que la barra "docs OK" no los recalcule en el cliente. El frontend ordena/secciona por `clasificacion` y muestra las filas faltantes como no accionables.

Alternativa descartada: mezclar faltantes dentro del mismo arreglo `documentos` con `id: null`. Se descarta porque las acciones (`validar`, `ver`) del frontend indexan por `documento.id`; separar faltantes evita ramas de `id null` en cada handler.

### 5. Sin migraciones
Todo el insumo ya está persistido (`checklist_documental_proceso`, `_items`, `documentos`, `validaciones_documento`, `vinculos_documento`). El change es puramente de presentación + regla de gating en Services + render.

## Risks / Trade-offs

- **[Cambio de tipo de retorno de `documentosDelCaso()` rompe llamadores]** → Es un método interno del dominio pago-proveedores; se ajustan sus dos únicos consumidores (`RevisionEgresoPresenter`, `todosAprobados` en el mismo service) en el mismo change, con tests que cubren ambos caminos.
- **[Un proceso sin checklist generado]** → `documentosDelCaso()` debe degradar con gracia: sin checklist, no hay obligatorios → no hay faltantes, y todos los documentos vinculados quedan como opcionales; el pago no se marca "listo por documentos" (coherente con que la revisión exige un checklist resuelto). Se cubre con test para no romper casos importados antes de generar checklist.
- **[Regla de gating más estricta que la actual]** → Ahora un obligatorio faltante bloquea aunque antes (con lista de documentos no vacía) pudiera aprobarse. Es el comportamiento deseado y explícito; se comunica como BREAKING de regla de negocio en el proposal.
- **[Doble fuente de "obligatorio": checklist item `tipo_requisito` vs. estado del documento]** → Se toma `tipo_requisito` del ítem del checklist como única fuente de obligatoriedad; el `estado_cumplimiento` del ítem no se usa para el gating de la revisión (el gating usa `estadoVigente` por instancia, que es lo correcto para Finanzas/Zonal independientes). Se documenta en el test que la obligatoriedad viene del checklist y la aprobación del estado por instancia.

## Migration Plan

Cambio de comportamiento sin datos que migrar. Despliegue: build de frontend + deploy backend. Rollback: revertir el change (los datos no cambian de forma). No requiere recomputar checklists existentes.

## Open Questions

Ninguna pendiente: las tres decisiones de producto (opcionales visibles y no bloqueantes, gating solo obligatorios, obligatorio faltante como fila que bloquea) fueron confirmadas por el usuario antes de esta propuesta.
