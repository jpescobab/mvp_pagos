## Why

Adquisiciones es uno de los módulos funcionales activables del harness, pero hoy no existe ningún dominio implementado para él: `modalidades_adquisicion` está vacía y no hay ningún `sujeto` de `Proceso` que represente un proceso de adquisición. Antes de integrar Mercado Público o construir páginas, el módulo necesita su modelo interno y su workflow propio, siguiendo el mismo patrón incremental que ya validó tres veces Pago de Proveedores (dominio primero, integración externa y UI después).

## What Changes

- Crear `procesos_adquisicion`: identificador interno propio (correlativo, no `sgf_id` — este módulo no tiene origen externo todavía), `modalidad_id` (FK a `modalidades_adquisicion`), `ccosto_id` responsable (jerarquía institucional), `proveedor_id` nullable, `monto` nullable, `objeto` (descripción del proceso de adquisición), `iniciado_por`.
- Registrar `ProcesoAdquisicion` como nuevo `sujeto` polimórfico de `Proceso`, igual patrón que `CasoPagoProveedor`.
- Crear una `DefinicionWorkflow` propia de adquisiciones con sus `EstadosWorkflow` y `TransicionesWorkflow` (detalle de estados/transiciones en `design.md`), gobernada exclusivamente por `TransicionWorkflowService::execute()` ya existente — no se crea lógica de transición nueva, se reutiliza el motor de `workflow-core`.
- Sembrar el catálogo `modalidades_adquisicion` (licitación pública, trato directo, convenio marco, etc.) y los permisos `adquisiciones.*` necesarios para las transiciones del nuevo workflow.
- Crear `ProcesoAdquisicionPolicy` con las reglas de autorización básicas (ver/crear), sin controladores HTTP todavía.
- Reutilizar `requisitos_documentales`/`ChecklistDocumentalProceso` (ya genéricos, sin cambios de spec) para que un proceso de adquisición pueda resolver su checklist documental por modalidad.
- Tests Pest que prueben el ciclo de vida del proceso (creación, transiciones válidas/bloqueadas, checklist) vía el servicio de workflow existente.
- **Fuera de alcance explícito**: sin controladores HTTP/Inertia, sin páginas React, sin integración con Mercado Público (snapshot/origen externo queda para un change posterior análogo a `sgf-origen-snapshot`).

## Capabilities

### New Capabilities
- `adquisiciones`: dominio interno y workflow de procesos de adquisición (modelo, catálogo de modalidades, definición de workflow propia, checklist documental), sin integración externa ni UI.

### Modified Capabilities
(ninguna — `workflow-core` y `documentos-expediente-variable` ya son genéricos para cualquier módulo funcional futuro y no requieren cambios de requirements)

## Impact

- **Nuevas migraciones**: `procesos_adquisicion`, `definiciones_workflow`/`estados_workflow`/`transiciones_workflow` ya existen como tablas genéricas (workflow-core) — solo se insertan filas nuevas vía seeder, no nuevas tablas de workflow.
- **Nuevos modelos**: `ProcesoAdquisicion`.
- **Nuevos seeders**: catálogo de `modalidades_adquisicion`, definición de workflow de adquisiciones (estados, transiciones, permisos).
- **Nueva policy**: `ProcesoAdquisicionPolicy`.
- **Sin cambios** en `TransicionWorkflowService`, `ChecklistDocumentalProceso`, ni en las tablas de `documentos-expediente-variable` — se reutilizan tal cual.
- **Sin impacto** en rutas, controladores ni frontend en este change.
