## Context

`documentos-expediente-variable` (tarea 06) ya define el modelo completo: `tipos_documento`, `conjuntos_requisitos_documentales`, `requisitos_documentales` (con `definicion_workflow_id`, `modalidad_id` que apunta a `ModalidadAdquisicion`, `estado_workflow_id`, `monto_desde`/`monto_hasta`), `checklists_documentales_proceso` y `ResolutorChecklistDocumentalProceso::resolve()`. Todo esto está implementado y testeado de forma aislada (`tests/Feature/Documentos/...` de la tarea 06), pero:

- Ningún seeder crea `tipos_documento` ni `requisitos_documentales` reales.
- Ningún controlador invoca `resolve()`. El campo `proceso.checklist` que ya consume el frontend (`adquisiciones/procesos/show.tsx` y `pago-proveedores/casos/show.tsx`) siempre llega `null` o vacío.
- `WorkflowAdquisicionesSeeder` ya declara `documentos_requeridos: ['CONTRATO']` en la transición `formalizar_contrato` (valida vía `ResolutorValidacionDocumental` contra `tipo_documento.codigo`), pero como no existe ningún `TipoDocumento` con código `CONTRATO`, esa validación nunca puede tener efecto real.

El usuario decidió explícitamente que el workflow interno de Adquisiciones no es prioritario (Mercado Público gobierna el proceso de compra real); lo que aporta valor institucional es la evidencia documental que CAPJ exige internamente sobre cada proceso.

## Goals / Non-Goals

**Goals:**
- Que el detalle de un `ProcesoAdquisicion` muestre un checklist real, distinto por modalidad, en vez de "Sin checklist generado aún".
- Reutilizar `ResolutorChecklistDocumentalProceso` y el modelo de reglas existente sin tocar su lógica.
- Alinear el `TipoDocumento` `CONTRATO` ya referenciado por `WorkflowAdquisicionesSeeder` para que la validación de documentos al transicionar a `contratada` tenga sentido real.

**Non-Goals:**
- No se modifican estados, transiciones ni permisos del workflow de Adquisiciones.
- No se construye UI de carga/validación de documentos (eso es un gap transversal, fuera de alcance — no existe para ningún módulo todavía).
- No se wirea `resolve()` para Pago de Proveedores; mismo gap, explícitamente fuera de alcance por ahora.
- No se integra con Mercado Público.

## Decisions

**D1 — Dónde invocar `resolve()`: en `show()`, no en `store()`/transiciones.**
La spec `documentos-expediente-variable` ya especifica el escenario "Generar checklist documental" como algo que ocurre `WHEN el usuario abre el expediente de un proceso`. Generarlo en `show()` cubre además el caso en que cambian las reglas o el estado del proceso entre visitas (cada apertura resuelve de nuevo). Alternativa descartada: generarlo solo en `store()` (Adquisiciones-only, una vez) — no reflejaría cambios de estado/monto posteriores.

**D2 — Un único `ConjuntoRequisitosDocumentales` para Adquisiciones, filtrado por `definicion_workflow_id`.**
El resolutor ya filtra por `definicion_workflow_id` además del conjunto, así que un conjunto por workflow es suficiente y consistente con el patrón "un conjunto = un módulo funcional" implícito en el diseño existente. No se crea un conjunto por modalidad.

**D3 — Reutilizar `TiposDocumentoSeeder` existente, no crear un catálogo paralelo.**
Ya existe `database/seeders/TiposDocumentoSeeder.php` (registrado en `DatabaseSeeder` antes de `WorkflowAdquisicionesSeeder`) con `CONTRATO` y `ACTA_RECEP` (acta de recepción de bienes/servicios) ya disponibles — se reutilizan tal cual. Se agregan a ese mismo seeder solo los códigos que faltan y son específicos de Adquisiciones: `BASES_LICITACION`, `RESOLUCION_ADJUDICACION` (distinto de `RESOLUCION`, que es "Resolución de Pago" y pertenece al dominio de Pago de Proveedores) y `GARANTIA`. Mantener un único catálogo evita duplicar conceptos (p. ej. dos códigos de "contrato").

**D4 — Reglas por modalidad usando `tipo_requisito` para distinguir obligatorio/opcional.**
- Licitación pública / privada: `BASES_LICITACION` (obligatorio), `RESOLUCION_ADJUDICACION` (obligatorio), `CONTRATO` (obligatorio), `GARANTIA` (obligatorio en pública, opcional en privada), `ACTA_RECEP` (obligatorio).
- Trato directo: `RESOLUCION_ADJUDICACION` (obligatorio), `CONTRATO` (obligatorio), `ACTA_RECEP` (obligatorio). Sin `BASES_LICITACION` (no aplica).
- Convenio marco: `CONTRATO` (obligatorio, la orden de compra hace de contrato), `ACTA_RECEP` (obligatorio).
Estas reglas son una primera aproximación razonable para destrabar el checklist; quedan abiertas a ajuste posterior por quien conozca el detalle normativo exacto (ver Open Questions).

**D5 — `resolve()` recibe el usuario autenticado para `generado_por`, igual que su firma actual.**
No requiere cambios en `ResolutorChecklistDocumentalProceso`; el controlador solo lo inyecta y llama con `Auth::user()`.

## Risks / Trade-offs

- [Riesgo] Las reglas de D4 son una aproximación inicial, no una definición normativa exacta de qué documento exige cada modalidad → Mitigación: quedan en una tabla de datos (seeder), fácilmente ajustable sin tocar código de dominio; se documenta como supuesto en proposal/tasks.
- [Riesgo] Regenerar el checklist en cada `show()` borra y recrea `checklist_documental_proceso_items` (comportamiento ya existente de `resolve()`), perdiendo el `estado_cumplimiento` capturado entre visitas si las reglas no cambiaron → Mitigación: está fuera de alcance modificar `resolve()`; se documenta como comportamiento heredado y conocido, no introducido por este cambio.
- [Riesgo] Pago de Proveedores queda con el mismo gap (checklist siempre vacío) → Mitigación: decisión explícita del usuario de no tocarlo en este cambio; no es una regresión, es el estado actual que se mantiene.

## Migration Plan

1. Seeder de `tipos_documento` (idempotente, `firstOrCreate` por `codigo`).
2. Seeder de `conjuntos_requisitos_documentales` + `requisitos_documentales` para Adquisiciones (idempotente).
3. Registrar el nuevo seeder en `DatabaseSeeder` (mismo patrón que los seeders de workflow existentes).
4. Wirear `ResolutorChecklistDocumentalProceso` en `ProcesoAdquisicionController::show()`.
5. Tests de regresión: cada modalidad resuelve sus documentos esperados; `show()` ya no devuelve checklist vacío para un proceso con modalidad asignada.

No requiere rollback especial: son datos de seeder y una llamada adicional de solo lectura/generación idempotente en un controlador `show()`.

## Open Questions

- Las reglas exactas de D4 (qué documento es obligatorio por modalidad) deberían validarse con quien maneje el proceso real de Adquisiciones en CAPJ; se implementan como mejor aproximación razonable, ajustable después sin tocar código.
