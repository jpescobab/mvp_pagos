## Context

`DefinicionWorkflow::estados()` y `::transiciones()` ya existen como relaciones `HasMany`; `EstadoWorkflow` tiene `es_inicial`/`es_final`, `TransicionWorkflow` tiene `permiso_requerido` (nullable), `documentos_requeridos` (json nullable) y `requiere_comentario` (boolean) — exactamente los flags que ya gatean el comportamiento de `TransicionWorkflowService::execute()` y de la UI de transiciones en `casos/show.tsx`/`procesos/show.tsx`. El precedente directo es `indicadores-economicos`: una página de solo lectura, sin Policy, gateada solo por el middleware `auth`, porque es información de referencia institucional sin dato sensible de ningún caso concreto.

## Goals / Non-Goals

**Goals:**
- Listar las `DefinicionWorkflow` existentes con su código, nombre, estado activo/inactivo y cantidad de estados/transiciones.
- Mostrar el detalle completo de una definición: estados (con `es_inicial`/`es_final`) y transiciones (origen → destino, permiso requerido, documentos requeridos, si exige comentario).

**Non-Goals:**
- No se renderiza un diagrama gráfico del state machine (nodos/flechas) — una tabla es suficiente para esta primera versión; un diagrama visual queda como mejora futura si se necesita.
- No se permite editar ni crear definiciones, estados o transiciones desde la UI — son datos sembrados por seeders de cada módulo, fuera de alcance de esta tarea.
- No se restringe el acceso a roles específicos: es información de gobierno institucional (qué reglas rigen cada módulo), no datos de ningún caso, proceso o usuario concreto.

## Decisions

1. **Sin Policy, solo middleware `auth`**, igual que `indicadores-economicos`. Las reglas de un workflow no son un dato sensible de un caso particular — son la documentación misma de cómo funciona el sistema, igual de pública internamente que el código fuente del seeder que ya cualquier desarrollador puede leer.
2. **Nueva capability `consulta-definiciones-workflow`** en vez de modificar `workflow-core`: `workflow-core` describe las reglas de ejecución de `TransicionWorkflowService` (motor), mientras que esto es una capacidad de consulta/reportabilidad sobre esos mismos datos — mismo criterio que separó `consulta-indicadores-economicos` de `indicadores-economicos-cmf-sii`.
3. **Listado + detalle (index/show), no una sola página con todo expandido.** Con 3 definiciones y hasta 13 transiciones cada una, una página de detalle por definición es más legible que una sola página con todo desplegado, mismo patrón ya usado en el resto del proyecto (casos, procesos, egresos CGU).

## Risks / Trade-offs

- **[Riesgo] Ninguno relevante** — es una exposición de solo lectura sobre datos ya sembrados, sin tocar `TransicionWorkflowService` ni ninguna transición real.
