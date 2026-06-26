## Context

Es el primer módulo funcional real sobre la infraestructura ya construida: workflow-core (tarea 5) gobierna estados/transiciones, documentos-expediente-variable (tarea 6) gobierna el expediente, sgf-origen-snapshot (tarea 7) entrega evidencia inmutable por `sgf_id`. Esta tarea conecta los tres: un `SnapshotSgf` se convierte en un `CasoPagoProveedor` gobernado, con su propio `Proceso`.

No hay acceso real a APIs de CGU ni BancoEstado (mismo caso que SGF en tarea 7), así que `registros_contables_cgu` y `registros_pago_bancario` se diseñan como evidencia registrada manualmente, no importada automáticamente.

## Goals / Non-Goals

**Goals:**
- Un `sgf_id` = un `caso_pago_proveedor` = un `Proceso` individual (regla no negociable del harness).
- `CasoPagoProveedorImporter` consume un `SnapshotSgf` y crea o actualiza el caso sin gobernar el workflow desde datos SGF.
- Workflow "pago_proveedores" sembrado con los 13 estados que sugiere `HARNESS_IA.md` sección 10.
- Evidencia de CGU, BancoEstado y egreso CGU sin replicar su lógica oficial.

**Non-Goals:**
- No se construyen lotes ni `payment_submissions` (prohibido explícitamente por el harness).
- No se construye UI ni endpoints HTTP (no estaba en alcance de ninguna tarea anterior).
- No se importa automáticamente desde CGU/BancoEstado (no hay acceso; es evidencia manual, igual que SGF en tarea 7 antes de tener conector).
- No se normaliza el formato de RUT más allá de una igualdad exacta contra `proveedores.rutproveedor` (sin datos reales para verificar variantes con/sin puntos o guion).

## Decisions

1. **`Proceso.sujeto` ya es polimórfico (tarea 5): `CasoPagoProveedor` no necesita una columna `proceso_id`.** Se define la relación inversa `CasoPagoProveedor::proceso(): MorphOne` apuntando a `Proceso` vía `sujeto`. Esto evita una dependencia circular al crear ambas filas (el caso no necesita saber el id del proceso, ni viceversa antes de existir) y reutiliza el diseño ya hecho en tarea 5 para "cualquier entidad de negocio futura".

2. **`CasoPagoProveedorImporter::importarDesdeSnapshot()` no usa `TransicionWorkflowService` para la creación inicial.** La asignación al estado inicial de un proceso nuevo no es una "transición" (no hay estado previo) — mismo criterio que ya usan los tests de tarea 5 (`crearProcesoDePrueba`) y el propio spec de workflow-core ("queda en el estado marcado como `es_inicial`"). Cualquier cambio de estado posterior (recibir en finanzas, aprobar documentación, etc.) sí pasa exclusivamente por `TransicionWorkflowService::execute()`.

3. **Reimportar un `sgf_id` existente actualiza solo los campos de referencia SGF** (`rut_proveedor`, `monto`, `sgf_status`, `sgf_current_group_raw`) y refleja el `monto` actualizado en `Proceso.monto` (para que la resolución de requisitos documentales por monto siga vigente), pero nunca toca `estado_actual_id` ni crea transiciones. El estado del proceso lo gobierna exclusivamente el flujo interno, no SGF.

4. **`facturas` es un dato estructurado distinto de `documentos` (tarea 6).** `documentos` (tipo `FACTURA`) guarda el archivo/evidencia; `facturas` guarda folio, monto y fecha de emisión como datos consultables, vinculados al caso y opcionalmente al proveedor. Ambos pueden coexistir para el mismo caso sin redundancia real (uno es archivo, otro es dato).

5. **`registros_contables_cgu` y `registros_pago_bancario` son evidencia de registro manual**, no snapshots de API (no hay acceso real): folio/operación, fecha, monto, observaciones y quién lo registró. Cuando exista integración real con CGU/BancoEstado (fuera de alcance de esta tarea), se podrá agregar un snapshot formal sin cambiar este esquema base.

6. **`egresos_cgu` puede cubrir varios casos a la vez**: relación muchos-a-muchos vía `egresos_cgu_items` (con `monto` por ítem, para repartir el egreso entre casos si corresponde). El respaldo documental opcional se modela reutilizando `vinculos_documento` (polimórfico, tarea 6) sobre `EgresoCgu`, en vez de agregar una columna de archivo nueva.

7. **Workflow "pago_proveedores" sembrado con los 13 estados sugeridos** (`importada_desde_sgf` es_inicial; `cerrada`, `rechazada`, `anulada` es_final) y 13 transiciones que cubren el flujo completo: recepción → revisión documental (con rama de observación/subsanación/rechazo/anulación) → registro CGU → pago BancoEstado → egreso CGU → cierre. La transición `aprobar_documentacion` exige el tipo documental `FACTURA` (`documentos_requeridos`), conectando con el expediente de tarea 6. Transiciones sensibles (`registrar_en_cgu`, `marcar_pagada_bancoestado`, `anular`) llevan `permiso_requerido`.

8. **Nombres en español desde el inicio** (sin rename posterior): `casos_pago_proveedor`, `facturas`, `registros_contables_cgu`, `registros_pago_bancario`, `egresos_cgu`, `egresos_cgu_items`; modelos `CasoPagoProveedor`, `Factura`, `RegistroContableCgu`, `RegistroPagoBancario`, `EgresoCgu`, `EgresoCguItem`; servicio `App\Services\PagoProveedores\CasoPagoProveedorImporter`.

## Risks / Trade-offs

- [Riesgo] El emparejamiento de proveedor por RUT exacto puede no encontrar coincidencia si el formato SGF difiere del formato guardado en `proveedores` (con/sin puntos, con/sin guion). → Mitigación: `proveedor_id` queda nullable; el caso se crea igual, solo sin vínculo a un proveedor existente, y `rut_proveedor` queda como referencia cruda de todas formas.
- [Riesgo] 13 estados y 13 transiciones es una matriz grande para mantener a mano en un seeder. → Mitigación: es exactamente la lista que ya sugiere `HARNESS_IA.md`; no se inventa alcance adicional. Las pruebas verifican el comportamiento genérico (ya cubierto por los tests de tarea 5) ejercitando una transición real con esta definición, no las 13 una por una.

## Migration Plan

- Migraciones nuevas, sin datos productivos. `php artisan migrate` agrega las tablas; nuevo seeder `WorkflowPagoProveedoresSeeder` se agrega a `DatabaseSeeder`.
