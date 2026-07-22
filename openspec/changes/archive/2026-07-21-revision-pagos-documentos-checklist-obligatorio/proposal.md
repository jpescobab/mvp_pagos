## Why

En la pantalla de Revisión de Pagos (ambas instancias: Finanzas y Zonal) la columna "Documentos del pago" muestra por igual **todos** los documentos activos vinculados al proceso — tanto los exigidos por el checklist documental como los documentos extra importados desde SGF (los "Otro Documento": BIT, CDP, formularios, etc.). Como el gating de aprobación exige aprobar cada documento visible, el revisor queda obligado a aprobar documentos accesorios que el checklist nunca requirió, y no distingue de un vistazo qué es obligatorio de qué es evidencia complementaria. El checklist documental del proceso ya distingue obligatorio de opcional por tipo de documento; la revisión debe apoyarse en él.

## What Changes

- La lista de documentos de la Revisión de Pagos se deriva del **checklist documental** del proceso: un documento importado es **obligatorio** si su `tipo_documento` coincide con un ítem del checklist con `tipo_requisito = obligatorio`; el resto de documentos importados son **opcionales**.
- **Presentación**: primero los obligatorios; debajo, una sección de **opcionales** con el resto de documentos importados. Todos siguen siendo consultables en el visor y aprobables/rechazables por instancia.
- **Obligatorio faltante**: por cada ítem obligatorio del checklist que **no** tenga documento importado, la revisión muestra una fila **pendiente/faltante** (placeholder, sin documento) que impide aprobar el pago.
- **BREAKING** (a nivel de regla de negocio del gating): la barra "docs OK", `listo_para_aprobar` y `todosAprobados` pasan a contar **solo los obligatorios** (obligatorios aprobados en la instancia activa **y** sin obligatorios faltantes). Los documentos opcionales pueden aprobarse/rechazarse, pero **no** bloquean la aprobación del pago ni el avance del Egreso.
- Se mantiene intacta la validación por instancia (Finanzas/Zonal independientes), la verificación de totales y que todo cambio de estado siga pasando por `TransicionWorkflowService`.

## Capabilities

### New Capabilities
<!-- Ninguna capability nueva: se ajusta el comportamiento documental de una existente. -->

### Modified Capabilities
- `revision-pagos-dos-instancias`: el requirement "Revisión documental por instancia" cambia el criterio de aprobación de "todos los documentos aprobados" a "todos los **obligatorios** del checklist aprobados y sin obligatorios faltantes"; se agrega la presentación separada obligatorios/opcionales y la fila de obligatorio faltante que bloquea la aprobación. El requirement "Pantalla de revisión de pagos condicionada por permiso e instancia" se refuerza: la pantalla recibe del backend la clasificación obligatorio/opcional/faltante (no la hardcodea).

## Impact

- **Backend (Services, sin tocar controladores)**:
  - `app/Services/PagoProveedores/ValidacionDocumentoInstanciaService.php` — `documentosDelCaso()` (clasificación) y `todosAprobados()` (gating solo obligatorios).
  - `app/Services/PagoProveedores/RevisionEgresoPresenter.php` — arma el payload con obligatorios/opcionales/faltantes.
  - `app/Services/PagoProveedores/RevisionEgresoService.php` — `pagoListoParaAprobar()` (gating solo obligatorios).
  - Lectura del checklist vía `ChecklistDocumentalProceso`/`ChecklistDocumentalProcesoItem` (generado por `ResolutorChecklistDocumentalProceso`); no se cambia cómo se genera el checklist.
- **Frontend**: `resources/js/pages/pago-proveedores/revision/index.tsx` — render de secciones obligatorios/opcionales y filas de obligatorio faltante; el gating de "docs OK"/Aprobar lo dicta el backend.
- **Sin migraciones**: no hay cambios de esquema; se reutilizan `checklist_documental_proceso(_items)`, `documentos`, `validaciones_documento` y los vínculos existentes.
- **Tests Feature**: obligatorio presente aprobado habilita; obligatorio faltante bloquea; opcional pendiente/rechazado no bloquea; comportamiento idéntico en Finanzas y Zonal.
- **Controladores livianos**: `RevisionPagosController` sigue delegando en el Presenter; ninguna lógica de cruce checklist↔documentos entra al controlador ni a React.
