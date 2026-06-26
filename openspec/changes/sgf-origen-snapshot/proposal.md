## Why

El harness exige que SGF sea origen, no gobierno: todo dato recibido desde SGF debe quedar como evidencia trazable (payload original, normalizado, hash, fuente, método de captura) sin que su estado o grupo gobiernen el workflow interno. Hoy no existe ningún mecanismo de captura/snapshot para SGF; antes de poder construir el módulo Pago de Proveedores (tarea 8, que consumirá estos snapshots para crear `casos_pago_proveedor`), se necesita la capa de evidencia que reciba filas SGF y las conserve de forma inmutable.

## What Changes

- Crear `importaciones_sgf`: registro de cada corrida de importación (fuente, quién/qué la inició, cuándo, cuántas filas, resultado).
- Crear `snapshots_sgf`: snapshot append-only por fila SGF importada (payload crudo, payload normalizado, hash, `sgf_id`, vinculado a la corrida que lo generó). Reimportar el mismo `sgf_id` crea un snapshot nuevo, nunca sobrescribe uno existente.
- Crear `snapshots_sgf_documentos`: vincula documentos recibidos desde SGF (reutilizando `documentos`/`versiones_documento` de la tarea de expediente documental) a su snapshot de origen.
- Crear `App\Services\Sgf\ImportadorSgf`: recibe filas SGF ya obtenidas (estructura: ID, estado, grupo actual, observaciones, RUT, documento(s), monto) — agnóstico de cómo se obtuvieron — y produce los snapshots correspondientes.

## Capabilities

### New Capabilities
- `sgf-origen-snapshot`: captura y conserva snapshots inmutables de filas y documentos SGF (payload crudo, normalizado, hash, fuente), sin gobernar workflow ni crear casos de pago.

### Modified Capabilities
(ninguna — esta tarea no modifica comportamiento de capacidades existentes; solo agrega tablas nuevas y un servicio nuevo. `documentos-expediente-variable` se reutiliza tal cual, sin cambios de spec.)

## Impact

- Migraciones nuevas: `importaciones_sgf`, `snapshots_sgf`, `snapshots_sgf_documentos`.
- Código nuevo: `App\Models\ImportacionSgf`, `SnapshotSgf`, `SnapshotSgfDocumento`; `App\Services\Sgf\ImportadorSgf`.
- Sin cliente HTTP/Playwright real a SGF (no hay acceso disponible; el conector real es alcance de la tarea 9 — integraciones API/Playwright). El importador recibe filas ya obtenidas por cualquier mecanismo futuro.
- Sin creación de `caso_pago_proveedor` ni `Proceso` (esa tabla y esa lógica son alcance de la tarea 8 — Pago de Proveedores).
