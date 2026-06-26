## Context

El spec original de `sgf-origen-snapshot` (prosa, pre-archivo) describe el escenario "Importar caso SGF" mencionando que se crea/actualiza un `caso_pago_proveedor`. Esa tabla no existe: la define `tasks/08_implementar_pago_proveedores_sgf.md`, secuenciada después de esta tarea. Esta tarea se escopa estrictamente a la capa de evidencia (import + snapshot); la creación del caso de pago gobernado y su `Proceso` quedan para tarea 8, que consumirá estos snapshots por `sgf_id`.

Tampoco existe acceso real a una API de SGF. El propio harness indica que SGF se conecta vía Playwright autorizado cuando no hay API suficiente — esa capa de conector (tarea 9: `browser_automation_*`, `external_systems`, etc.) todavía no se construye. Esta tarea no debe inventar un cliente sin poder verificarlo contra el sistema real.

Todos los nombres de tabla/modelo de esta tarea se definen directamente en español (ver decisión 6), siguiendo el rename ya aplicado a workflow-core y documentos-expediente-variable.

## Goals / Non-Goals

**Goals:**
- Modelo de datos para conservar evidencia inmutable de cada fila SGF importada: payload crudo, payload normalizado, hash, fuente y método de captura.
- Servicio de importación (`ImportadorSgf`) agnóstico de cómo se obtuvieron las filas (manual, job futuro, o el conector Playwright de tarea 9).
- Reutilizar el modelo documental de `documentos-expediente-variable` (`Documento`/`VersionDocumento`) para los documentos que SGF entrega junto a cada fila.

**Non-Goals:**
- No se crea `caso_pago_proveedor` ni se invoca `TransicionWorkflowService` desde esta tarea (tarea 8).
- No se construye un cliente HTTP ni un conector Playwright real a SGF (tarea 9). El importador es el punto de entrada; quién lo invoque y cómo obtenga las filas es responsabilidad de una tarea futura.
- No se modela actualización in-place de un snapshot existente: cada importación de un `sgf_id` crea un snapshot nuevo (historial append-only), nunca se sobrescribe uno previo.

## Decisions

1. **`importaciones_sgf` es el registro de la corrida, `snapshots_sgf` es el registro por fila.** Separar ambos permite saber cuántas filas trajo cada corrida, cuándo, con qué resultado, sin mezclar eso con el contenido de cada fila. Mismo patrón que `indicadores_economicos_importaciones` (tarea 4) frente a `indicadores_economicos`.

2. **`snapshots_sgf` es append-only por diseño**: no hay `unique(sgf_id)` global — el mismo `sgf_id` puede tener múltiples snapshots a lo largo del tiempo (uno por cada vez que SGF lo entregó). Sí hay `unique(importacion_sgf_id, sgf_id)` para evitar duplicar la misma fila dentro de una misma corrida. La fila vigente de un `sgf_id` es la de mayor `id`, igual al patrón ya usado para `validaciones_documento` (corregido tras detectar que ordenar por `created_at` es ambiguo si dos eventos caen en el mismo segundo).

3. **`payload_crudo` y `payload_normalizado` se guardan ambos como json.** `payload_crudo` es exactamente lo recibido (sin tipar); `payload_normalizado` aplica el mismo tipo de normalización usada para CMF (números chilenos, fechas) sobre los campos que el harness define (sgf_id, estado, grupo, observaciones, rut, monto). Esto preserva la regla de snapshot obligatorio sin perder la capacidad de consultar campos tipados.

4. **`snapshots_sgf_documentos` es una tabla de unión, no un documento nuevo.** Un documento que SGF entrega se crea como `Documento`/`VersionDocumento` real (tarea 6), y esta tabla solo vincula ese documento a su snapshot de origen. No se agrega una columna `origen` a `documentos`: la existencia de una fila en esta tabla de unión ya prueba que el documento vino de SGF, evitando un campo redundante en una tabla que la tarea anterior diseñó deliberadamente agnóstica de origen.

5. **`ImportadorSgf::importarFila()` recibe un array ya obtenido, no hace ningún HTTP call.** Firma: `importarFila(ImportacionSgf $importacion, array $filaSgf): SnapshotSgf`. Quien orqueste la obtención de filas (comando manual hoy, conector Playwright en tarea 9) crea primero el `ImportacionSgf` (la corrida) y le pasa cada fila. Esto deja el servicio testeable con fixtures sin depender de acceso real a SGF.

6. **Nombres en español desde el inicio** (no requieren rename posterior): `importaciones_sgf`, `snapshots_sgf`, `snapshots_sgf_documentos`; modelos `ImportacionSgf`, `SnapshotSgf`, `SnapshotSgfDocumento`; servicios `App\Services\Sgf\ImportadorSgf`, `App\Services\Sgf\NormalizadorSgf`. `sgf_id`, `sgf_status`, `sgf_current_group_raw` no se traducen: son identificadores literales del sistema externo SGF, igual que los códigos institucionales.

## Risks / Trade-offs

- [Riesgo] Sin una fila SGF real, la forma exacta de `payload_crudo` es una suposición basada en los campos que lista el harness (ID, estado, grupo actual, observaciones, RUT, documento, monto). → Mitigación: `payload_crudo` es json sin esquema fijo en base de datos; si el formato real difiere al conectar el conector de tarea 9, solo cambia la normalización (`payload_normalizado`), no el esquema de tablas.
- [Riesgo] Sin tabla `caso_pago_proveedor` todavía, no hay forma de probar end-to-end que un snapshot efectivamente origina un caso gobernado. → Mitigación: es exactamente el trabajo de tarea 8, inmediatamente después; esta tarea se valida de forma aislada (snapshot se crea, conserva hash, no se sobrescribe).

## Migration Plan

- Migraciones nuevas, sin datos productivos que migrar. `php artisan migrate` agrega las tablas sin afectar las existentes.
