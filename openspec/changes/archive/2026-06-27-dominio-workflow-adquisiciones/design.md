## Context

`workflow-core` (tarea 5) ya gobierna estados/transiciones de forma genérica para "cualquier entidad de negocio futura" vía `Proceso.sujeto` polimórfico, y `documentos-expediente-variable` (tarea 6) ya resuelve checklists por workflow/modalidad/monto/estado sin acoplarse a ningún módulo. `modalidades_adquisicion` existe desde tarea 6 pero está vacía — nadie la ha sembrado ni la ha usado todavía. Este change conecta esa infraestructura genérica con el primer dominio propio de Adquisiciones, replicando el mismo patrón que conectó workflow-core + documentos-expediente-variable con Pago de Proveedores en tarea 8, pero sin un sistema de origen externo (SGF) detrás: aquí no hay todavía ningún snapshot ni importador, los procesos de adquisición se crean internamente.

## Goals / Non-Goals

**Goals:**
- Un modelo `ProcesoAdquisicion` que sea `sujeto` de un `Proceso`, igual patrón que `CasoPagoProveedor`.
- Workflow `adquisiciones` sembrado con estados y transiciones suficientes para un ciclo de vida completo (borrador → revisión → publicación → adjudicación → contrato → cierre, con ramas de rechazo/anulación), gobernado exclusivamente por `TransicionWorkflowService::execute()` ya existente.
- Sembrar `modalidades_adquisicion` (hoy vacía) con modalidades reales de compra pública chilena.
- Checklist documental de un proceso de adquisición resuelto vía `requisitos_documentales` filtrados por `modalidad_id`, reutilizando el catálogo de `tipos_documento` ya existente (no se agregan tipos nuevos en este change; `CONTRATO` ya cubre la transición que lo necesita).

**Non-Goals:**
- No hay integración con Mercado Público ni ningún snapshot/origen externo (queda para un change posterior análogo a `sgf-origen-snapshot`).
- No hay controladores HTTP, rutas ni páginas React.
- No se define algoritmo formal de generación de `codigo` interno (correlativo institucional) — se asigna explícitamente al crear, como en cualquier seeder/test/factory; el algoritmo real puede llegar junto con la integración externa.
- No se agregan `tipos_documento` nuevos al catálogo (se reutiliza `CONTRATO`, ya sembrado).

## Decisions

1. **`ProcesoAdquisicion` no duplica `iniciado_por`.** `Proceso` ya tiene esa columna (igual que `CasoPagoProveedor` no la repite); `procesos_adquisicion` solo guarda datos propios del proceso de adquisición: `codigo` (interno, unique), `modalidad_id` (FK `modalidades_adquisicion`, requerido — gobierna el checklist), `ccosto_id` (FK `ccostos`, requerido — unidad responsable dentro de la jerarquía institucional), `proveedor_id` (FK `proveedores`, nullable — puede no haber proveedor asignado en etapas tempranas), `monto` (nullable), `objeto` (text, descripción del proceso).

2. **`ProcesoAdquisicion::proceso(): MorphOne`** apunta a `Proceso` vía `sujeto`, igual que `CasoPagoProveedor::proceso()` (tarea 8, decisión 1). Evita dependencia circular al crear ambas filas.

3. **`App\Services\Adquisiciones\ProcesoAdquisicionService::crear(array $datos): ProcesoAdquisicion`** crea ambas filas en una transacción: el `ProcesoAdquisicion` y su `Proceso` asociado, asignando `estado_actual_id` al estado `es_inicial` de la definición `adquisiciones`. Igual que con `CasoPagoProveedorImporter::importarDesdeSnapshot()` (tarea 8, decisión 2), esta asignación inicial no pasa por `TransicionWorkflowService` porque no es una transición (no hay estado previo) — coincide con el propio spec de `workflow-core` ("queda en el estado marcado como `es_inicial`"). Cualquier cambio de estado posterior sí pasa exclusivamente por `TransicionWorkflowService::execute()`.

4. **Workflow `adquisiciones` sembrado con 8 estados y 8 transiciones**, deliberadamente más simple que los 13/13 de `pago_proveedores` porque este slice es interno (sin presión de un sistema de origen real todavía):
   - Estados: `borrador` (es_inicial), `en_revision`, `publicada`, `adjudicada`, `contratada`, `cerrada` (es_final), `rechazada` (es_final), `anulada` (es_final).
   - Transiciones: `enviar_a_revision` (borrador→en_revision), `devolver_a_borrador` (en_revision→borrador, requiere_comentario), `publicar` (en_revision→publicada, permiso `adquisiciones.publicar`), `adjudicar` (publicada→adjudicada, permiso `adquisiciones.adjudicar`), `formalizar_contrato` (adjudicada→contratada, documentos_requeridos: `['CONTRATO']`), `cerrar` (contratada→cerrada), `rechazar` (en_revision→rechazada, requiere_comentario), `anular` (en_revision→anulada, requiere_comentario, permiso `adquisiciones.anular`).
   - El flujo real de una licitación pública (Ley 19.886/Mercado Público) tiene más fases regladas; se ajustará cuando exista integración externa real, igual que `pago_proveedores` se ajustó con datos reales de SGF entre tarea 7 y 8.

5. **`modalidades_adquisicion` se siembra con modalidades reales** (`LICITACION_PUBLICA`, `LICITACION_PRIVADA`, `TRATO_DIRECTO`, `CONVENIO_MARCO`), suficientes para que la resolución de checklist por modalidad tenga datos reales con los que operar, sin inventar reglas de negocio de Mercado Público que no se han confirmado.

6. **Permisos nuevos**: `adquisiciones.publicar`, `adquisiciones.adjudicar`, `adquisiciones.anular`, otorgados al rol `admin` (mismo patrón que `WorkflowPagoProveedoresSeeder`).

7. **Nombres en español desde el inicio**: tabla `procesos_adquisicion`, modelo `ProcesoAdquisicion`, servicio `App\Services\Adquisiciones\ProcesoAdquisicionService`, policy `ProcesoAdquisicionPolicy`, seeder `WorkflowAdquisicionesSeeder`.

## Risks / Trade-offs

- [Riesgo] 8 estados/transiciones genéricos pueden no calzar con el flujo real de Mercado Público una vez se integre. → Mitigación: explícitamente fuera de alcance este change; se ajustará con datos reales en el change de integración, igual que pasó con SGF.
- [Riesgo] `codigo` sin algoritmo de generación puede colisionar si dos personas crean procesos a la vez sin coordinarse. → Mitigación: columna `unique`; el algoritmo de correlativo real se diseñará junto con la integración externa, cuando se sepa el formato que use Mercado Público (si alguno) o el correlativo institucional definitivo.

## Migration Plan

Migraciones nuevas (`procesos_adquisicion`), sin datos productivos. `php artisan migrate` agrega la tabla; nuevo seeder `WorkflowAdquisicionesSeeder` se agrega a `DatabaseSeeder`, junto con el llenado de `modalidades_adquisicion`.
