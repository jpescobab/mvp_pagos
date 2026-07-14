## Context

`resources/js/pages/pago-proveedores/casos/show.tsx` es la página de detalle de un `caso_pago_proveedor`, ya cubierta por la capability `paginas-pago-proveedores`. El checklist documental (`ChecklistDocumentalCard`) ya permitía subir un documento a un ítem pendiente y vincular un "huérfano"; solo faltaba una forma de revisar el archivo ya vinculado sin descargarlo, y de deshacer una vinculación equivocada sin salir de la página.

Al tocar `ResolutorChecklistDocumentalProceso` para depurar por qué la matriz de requisitos (`administracion-requisitos-documentales-pago-proveedores`) no dejaba marcar "no aplica" sobre ciertas filas, aparecieron los dos bugs de fondo descritos en el proposal.

## Goals / Non-Goals

**Goals:**
- Vista previa embebida de un documento vinculado sin forzar la descarga.
- Desvincular un documento directamente desde el checklist, reusando el endpoint de desvinculación ya existente (`DocumentoProcesoController::destroy()`), solo agregando el control en la UI del checklist.
- Corregir los dos bugs de integridad/consistencia encontrados en el camino.

**Non-Goals:**
- No se rediseña el resto de la página fuera de la cabecera y el layout de checklist/preview (los paneles de preparación, transiciones e historial no cambian de comportamiento).
- No se agrega un permiso nuevo — `ver` reutiliza la misma autorización implícita de acceso al proceso que ya tiene `descargar` (ambos son rutas autenticadas sin chequeo de permiso adicional, consistente con el resto de `DocumentoProcesoController`).

## Decisions

**1. `ver()` es un método nuevo en `DocumentoProcesoController`, no un parámetro en `descargar()`.**
Mantener dos rutas explícitas (`descargar` con `Content-Disposition: attachment`, `ver` con disposition inline vía `response()->file()`) es más simple y explícito en las rutas de Wayfinder que un query param que cambie el header de la misma acción. Sigue el precedente ya existente de `RevisionVerDocumentoController::show()` en el módulo de Revisión de Pagos.

**2. `cascadeOnDelete()` en vez de manejar la limpieza de `checklist_documental_proceso_items` a mano antes de borrar un `RequisitoDocumental`.**
Esos items son el resultado cacheado de la última resolución del checklist (`ResolutorChecklistDocumentalProceso`), no evidencia — la siguiente vez que se abre el detalle del proceso se regeneran igual. Bloquear la eliminación del `RequisitoDocumental` (comportamiento anterior, `restrictOnDelete()`) no protegía nada real y sí rompía la matriz.

**3. El filtro `activo` del `TipoDocumento` se aplica en `requisitosAplicables()`, no se borra el `RequisitoDocumental` al desactivar el tipo.**
Desactivar un `TipoDocumento` es un soft-toggle (ver `administracion-requisitos-documentales-pago-proveedores`); el `RequisitoDocumental` que lo referencia sigue existiendo para no perder la configuración, pero deja de aparecer en checklists nuevos mientras el tipo esté inactivo — mismo patrón que ya usan las páginas de selección de `TipoDocumento`/`TipoProcesoPago` (`where('activo', true)`).

## Migration Plan

1. Migración `2026_07_14_140000_change_requisito_documental_id_cascade_on_checklist_items.php`: cambia la FK de `restrictOnDelete()` a `cascadeOnDelete()`. Reversible (`down()` restaura `restrictOnDelete()`).
2. Sin backfill de datos — el comportamiento nuevo aplica hacia adelante; los `checklist_documental_proceso_items` existentes no se tocan.
3. Deploy normal (backend + `npm run build`), sin downtime.
