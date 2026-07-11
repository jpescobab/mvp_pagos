## Why

Los pagos importados desde SGF necesitan una revisión formal, trazable y con separación de funciones antes de avanzar al registro CGU. Hoy el workflow de Pago de Proveedores tiene una sola etapa de revisión documental (`en_revision_documental`), sin instancias diferenciadas ni una pantalla de trabajo para el revisor. Se requiere una **revisión en dos instancias secuenciales** — primero Jefe de Finanzas, luego Administrador Zonal — operando sobre **Egresos** (agrupaciones de pagos), con devolución siempre posible a la instancia anterior.

## What Changes

- **Dos instancias de revisión** que expanden la etapa documental del workflow del caso, antes del registro CGU: `en_revision_finanzas` → `en_revision_zonal`. Un pago solo pasa a Zonal cuando Finanzas lo aprueba; siempre puede devolverse de Zonal a Finanzas (con comentario obligatorio).
- **Revisión pago por pago dentro del Egreso**: cada revisor aprueba/rechaza cada pago y cada uno de sus documentos. El Egreso avanza a la instancia siguiente solo cuando **todos** sus pagos fueron aprobados por la instancia actual. El estado de revisión del Egreso es **derivado** de los estados de sus casos, no se persiste como fuente de verdad.
- **Ambas instancias revisan documento por documento**. Se agrega la dimensión de instancia a la validación documental (`validaciones_documento.instancia` = `finanzas` | `zonal`), de modo que un documento validado por Finanzas vuelve a estar pendiente para el Administrador Zonal sin perder el rastro de la validación previa. Cada aprobación de pago exige que todos sus documentos estén aprobados **en la instancia activa** y que los totales del pago (factura vs. recepción/OC vs. monto a pagar) estén verificados.
- **Roles y permisos nuevos**: roles `jefe_finanzas` y `administrador_zonal`; permisos `pago_proveedores.revisar_finanzas` y `pago_proveedores.revisar_zonal`. El Administrador Zonal solo ve y actúa sobre Egresos de **su jurisdicción/zona** (scope resuelto vía policy contra la jurisdicción derivada del Egreso).
- **Formación de Egresos automática + manual**: al importar desde SGF los pagos se agrupan automáticamente en Egresos por período + centro financiero (garantizando que cada Egreso quede dentro de una sola zona), y se permite ajustar la agrupación manualmente antes de enviar a revisión (la creación manual de `EgresoCgu` ya existe).
- **Pantalla React de Revisión de Pagos** (`pago-proveedores/revision`) que reproduce el flujo del prototipo: strip de Egresos pendientes → strip de pagos del Egreso → documentos + visor + panel de revisión (totales, aprobar/rechazar por documento con motivo, aprobar/rechazar/devolver pago). Las acciones se condicionan por `auth.permissions` y por la instancia activa del Egreso.
- Todas las transiciones de estado se ejecutan exclusivamente a través de `TransicionWorkflowService::execute()`; la aprobación/devolución a nivel de Egreso itera sobre los casos y dispara la transición de cada uno.

## Capabilities

### New Capabilities
- `revision-pagos-dos-instancias`: Revisión en dos instancias secuenciales (Jefe de Finanzas → Administrador Zonal) de los pagos agrupados en Egresos, con revisión documental por instancia, verificación de totales, avance del Egreso cuando todos sus pagos se aprueban, devolución a la instancia anterior y scope zonal por jurisdicción.

### Modified Capabilities
- `pago-proveedores-sgf`: El workflow del caso expande su etapa de revisión documental en dos instancias diferenciadas (`en_revision_finanzas`, `en_revision_zonal`) con sus transiciones de avance, devolución y rechazo; la agrupación automática de pagos en Egresos al importar desde SGF.
- `documentos-expediente-variable`: La validación documental (`validaciones_documento`) se registra por instancia de revisión, permitiendo que el mismo documento sea validado independientemente por Finanzas y por el Administrador Zonal.

## Impact

- **Workflow**: `WorkflowPagoProveedoresSeeder` (nuevos estados y transiciones), `TransicionWorkflowService` (sin cambios en su contrato; se reutiliza).
- **Modelos/migraciones**: `validaciones_documento` (columna `instancia` + índice), `egresos_cgu` (campos para agrupación/derivación de zona: `periodo`, `cfinanciero_id` si no se derivan), `EgresoCgu`/`EgresoCguItem`/`CasoPagoProveedor` (relaciones y accesores derivados).
- **Servicios**: nuevo `RevisionEgresoService` (avance/devolución del Egreso vía workflow, derivación de estado e instancia activa), servicio de validación documental por instancia, extensión de `CasoPagoProveedorImporter` (agrupación automática en Egresos).
- **Autorización**: `RolesAndPermissionsSeeder` (roles + permisos), nueva/actualizada `EgresoCguPolicy` (scope zonal).
- **HTTP/rutas**: `routes/pago-proveedores.php` (rutas de revisión, validación de documento y transición de Egreso), controladores livianos + Form Requests + Resources.
- **Frontend**: nueva pantalla `resources/js/pages/pago-proveedores/revision/`, ítems de sidebar condicionados por permiso, helpers Wayfinder regenerados.
- **Tests**: `tests/Feature/PagoProveedores/` (flujo Finanzas→Zonal, devolución, bloqueo por documentos pendientes/totales sin verificar, scope zonal, agrupación automática, doble validación por instancia).
- **Sin cambios de dependencias.** No se toca SGF como gobierno; SGF sigue siendo solo origen/evidencia.
