## Why

Las tareas 1-9 construyeron dominio institucional, workflow, expediente documental, evidencia SGF, el primer módulo funcional (pago de proveedores) y la capa transversal de integraciones, pero no existe ningún mecanismo para congelar esos datos en un corte periódico ni para producir informes razonados de gestión a partir de evidencia estable. Sin esta tarea, cualquier reporte tendría que construirse sobre datos vivos cambiantes, violando la regla rectora del harness de que los informes razonados nacen de cortes y snapshots, nunca de datos en movimiento, y siempre terminan con revisión humana antes de publicarse.

## What Changes

- Crear la capa core de reportabilidad (no desactivable, igual que indicadores e integraciones):
  - `periodos_reportabilidad`: períodos de corte (p. ej. un mes).
  - `cortes_reportabilidad`: el acto de cortar/congelar datos para un período (borrador/publicado).
  - `cortes_reportabilidad_items`: qué entidades internas quedaron incluidas en un corte (vínculo polimórfico).
  - `snapshots_corte_reportabilidad`: evidencia inmutable (payload crudo, hash) capturada en un corte.
- Crear `App\Services\Reportabilidad\CorteReportabilidadService` con `abrirPeriodo()`, `crearCorte()`, `agregarItem()`, `capturarSnapshot()` y `publicarCorte()` (exige permiso `reportabilidad.publicar_corte`; rechaza modificar un corte ya publicado).
- Crear el módulo funcional activable "informes_razonados" (mismo mecanismo de activación que `pago_proveedores`, vía `DefinicionWorkflow.activo`):
  - `definiciones_informe_razonado`: catálogo de tipos/plantillas de informe.
  - `ejecuciones_informe_razonado`: una corrida de informe sobre un corte ya publicado, con su propio `Proceso` de workflow (`borrador` → `en_revision` → `aprobado`/`rechazado` → `publicado`).
  - `secciones_informe_razonado`, `metricas_informe_razonado`, `graficos_informe_razonado`, `excepciones_informe_razonado`: contenido estructurado del informe.
  - `narrativas_informe_razonado`: texto razonado en borrador, con marca explícita `generado_por_ia` y campos de revisión humana — la IA puede redactar, nunca aprobar.
  - `snapshots_informe_razonado`: evidencia inmutable del contenido final del informe, capturada al publicarlo.
  - `aprobaciones_informe_razonado`: decisión humana (aprobar/rechazar) registrada junto a la transición de workflow correspondiente.
  - `exportaciones_informe_razonado`: registro de cada exportación (Word/PDF/Excel/HTML) generada.
- Crear `App\Services\InformesRazonados\InformeRazonadoService` que orquesta el ciclo completo, delegando todo cambio de estado de la ejecución a `TransicionWorkflowService::execute()` — nunca actualiza el estado directamente.
- Sembrar `WorkflowInformesRazonadosSeeder`: permisos (`reportabilidad.publicar_corte`, `informes.aprobar`, `informes.publicar`) y la `DefinicionWorkflow` "informes_razonados" con sus estados y transiciones.
- Crear `App\Exceptions\CorteReportabilidadException` para los guardas de permiso/inmutabilidad de cortes y la precondición de que un informe solo puede iniciarse sobre un corte publicado.

## Capabilities

### New Capabilities
- `reportabilidad-informes-razonados`: capa de cortes/snapshots de reportabilidad (core) e informes razonados de gestión (módulo funcional activable). Los informes no son cierres contables ni presupuestarios oficiales; son evidencia de gestión, seguimiento y toma de decisiones, siempre con revisión y aprobación humana antes de publicarse.

### Modified Capabilities
(ninguna — no cambia comportamiento de `workflow-core`, `pago-proveedores-sgf`, `integraciones-api-browser-automation` ni del resto; los consume como infraestructura ya existente.)

## Impact

- Migraciones nuevas: `periodos_reportabilidad`, `cortes_reportabilidad`, `cortes_reportabilidad_items`, `snapshots_corte_reportabilidad`, `definiciones_informe_razonado`, `ejecuciones_informe_razonado`, `secciones_informe_razonado`, `metricas_informe_razonado`, `graficos_informe_razonado`, `excepciones_informe_razonado`, `narrativas_informe_razonado`, `snapshots_informe_razonado`, `aprobaciones_informe_razonado`, `exportaciones_informe_razonado`.
- Código nuevo: 14 modelos Eloquent; servicios `CorteReportabilidadService` e `InformeRazonadoService`; excepción `CorteReportabilidadException`.
- Nuevo seeder: `WorkflowInformesRazonadosSeeder`.
- No se implementa generación real de archivos Word/PDF/Excel/HTML ni motor de gráficos; `exportaciones_informe_razonado` solo registra la evidencia de que una exportación ocurrió (ruta de archivo, formato, responsable), igual que tareas anteriores registraron evidencia sin replicar sistemas externos.
- No se construye UI ni endpoints HTTP en esta tarea (igual que tareas 5-9); es la capa de dominio/servicio final del harness.
- Última tarea numerada (10 de 10) — al completarse, todo el dominio institucional descrito en `tasks/01_*.md` a `tasks/10_*.md` queda implementado.
