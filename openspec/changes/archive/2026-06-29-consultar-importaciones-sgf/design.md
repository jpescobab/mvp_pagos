## Context

`ImportacionSgf::snapshots(): HasMany` ya existe (una corrida produce muchos snapshots, potencialmente de distintos `sgf_id`). El servicio `ImportadorSgf` ya es testeado de forma aislada (`tests/Feature/PagoProveedores/CasoPagoProveedorImporterTest.php`), pero nada en la capa HTTP lo consume todavía. El historial de snapshots por caso (`CasoPagoProveedor::snapshotsSgf()`, change `mostrar-historial-snapshots-sgf`) responde la pregunta desde el lado del caso; esta tarea responde la pregunta desde el lado de la corrida — útil para detectar, por ejemplo, una importación que quedó `en_progreso` sin `finalizado_en`.

## Goals / Non-Goals

**Goals:**
- Listar las `ImportacionSgf` con fuente, quién la inició, fechas de inicio/fin, total de filas y estado, ordenadas de la más reciente a la más antigua.
- Mostrar el detalle de una importación con todos los `snapshots_sgf` que produjo.

**Non-Goals:**
- No se agrega ninguna acción para iniciar, reintentar o finalizar manualmente una importación desde la UI — eso es responsabilidad exclusiva del job/comando que orquesta `ImportadorSgf` (hoy manual, futuro conector Playwright en tarea 9), fuera de alcance de esta tarea de solo lectura.
- No se restringe el acceso a un permiso nuevo: es visibilidad operativa sobre corridas de un proceso batch, no un dato de negocio sensible de un caso o proveedor concreto — mismo criterio que `indicadores-economicos` y `consulta-definiciones-workflow`, distinto del de `auditoria.ver` (que sí protege diffs de acciones de negocio).

## Decisions

1. **Sin Policy, solo middleware `auth`**, igual que `indicadores-economicos` y `consulta-definiciones-workflow`. El detalle expone `sgf_id`/`hash`/`capturado_en` de cada snapshot, el mismo nivel de dato que ya es visible sin restricción adicional en el historial de snapshots de cualquier caso.
2. **Nueva capability `consulta-importaciones-sgf`** en vez de modificar `sgf-origen-snapshot`: ese spec describe la captura/conservación (motor), esta tarea es una capacidad de consulta sobre esos mismos datos — mismo criterio que separó `consulta-indicadores-economicos` y `consulta-definiciones-workflow` de sus respectivos dominios de captura/ejecución.
3. **El ítem de navegación va bajo "Pago de Proveedores"**, no en "General": SGF hoy solo alimenta ese módulo funcional (único consumidor real de `CasoPagoProveedorImporter`); si en el futuro otro módulo consume SGF, este ítem se puede mover a "General" sin cambiar el backend.

## Risks / Trade-offs

- **[Riesgo] Ninguno relevante** — es una exposición de solo lectura sobre datos ya sembrados/capturados, sin tocar `ImportadorSgf` ni ningún flujo de escritura.
