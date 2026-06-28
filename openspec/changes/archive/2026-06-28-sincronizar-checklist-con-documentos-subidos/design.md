## Context

`ChecklistDocumentalProcesoItem` ya tiene `documento_id` (nullable, `belongsTo`) y `estado_cumplimiento` (string libre, default `pendiente`). `resolve()` borra y recrea todos los items en cada llamada (comportamiento ya existente, no se cambia), siempre con `documento_id: null` y `estado_cumplimiento: 'pendiente'`. `Documento::estadoVigente()` ya devuelve `'pendiente'` (sin validar), `'valido'` o `'rechazado'` según el último evento en `validaciones_documento`. El cambio anterior (`subir-vincular-documentos-proceso`) ya deja `VinculoDocumento.activo` consultable por `Proceso::vinculosDocumento()`.

## Goals / Non-Goals

**Goals:**
- Que un documento subido para el `tipo_documento` exacto que un item del checklist exige quede reflejado en ese item (`documento_id` + `estado_cumplimiento` coherente).
- Distinguir "nada subido" de "subido pero sin validar" de "validado"/"rechazado".

**Non-Goals:**
- No se construye el flujo de validación (`validaciones_documento`) — se sigue sin poder crear esos eventos vía HTTP; este cambio solo lee el estado si ya existe (por ahora, vía tinker/seeder o una futura feature).
- No se decide automáticamente si el checklist completo está "aprobado" — eso es agregación a nivel de proceso, no de item individual, y queda fuera de alcance.
- No se cambia qué requisitos aplican (eso lo sigue resolviendo `requisitosAplicables()` sin tocar).

## Decisions

**D1 — Match por `tipo_documento_id`, no por `requisito_documental_id`.**
Un documento no "sabe" para qué requisito se subió, solo su `tipo_documento_id` (p. ej. "Contrato"). Si un proceso tiene varios requisitos del mismo `tipo_documento_id` (raro pero posible, p. ej. dos reglas distintas que coincidan en tipo), todos se consideran satisfechos por el mismo documento — es el comportamiento más simple y razonable sin introducir un concepto nuevo de "documento por requisito".

**D2 — Si hay varios documentos activos del mismo tipo, gana el más reciente.**
`VinculoDocumento` más reciente (por `created_at`) cuyo `documento.tipo_documento_id` coincida y esté `activo`. Coherente con "última evidencia vigente"; versionar/elegir cuál es el documento "correcto" entre varios del mismo tipo es una decisión de negocio fuera de alcance — se toma la más simple por ahora.

**D3 — Nuevo valor `cargado` para `estado_cumplimiento`.**
Sin un estado intermedio, "subido sin validar" sería indistinguible de "nada subido" (ambos `estadoVigente() === 'pendiente'`). Se introduce `cargado` específicamente para el caso "hay `documento_id` pero su `estadoVigente()` es `pendiente`". Cuando `estadoVigente()` es `valido` o `rechazado`, se usa ese valor tal cual (ya son suficientemente descriptivos).

**D4 — La consulta de documentos vinculados vive dentro de `ResolutorChecklistDocumentalProceso`, no en el controlador.**
Mantiene la regla de negocio encapsulada en el service, igual que `requisitosAplicables()`; el controlador no cambia.

## Risks / Trade-offs

- [Riesgo] Si dos requisitos distintos comparten `tipo_documento_id` con distinto `tipo_requisito` (uno obligatorio, otro opcional), ambos se marcarán `cargado` con el mismo documento, aunque conceptualmente sean exigencias distintas → Mitigación: es un caso de configuración de datos (seeders de `requisitos_documentales`), no de lógica; aceptable para esta iteración, documentado aquí para quien configure la matriz a futuro.
- [Riesgo] `cargado` es un valor nuevo no presente en ningún enum/cast (la columna es string libre) → Mitigación: ya era el patrón existente (`pendiente` tampoco está en un enum); no requiere migración.

## Migration Plan

1. Modificar `ResolutorChecklistDocumentalProceso::resolve()` para resolver `documento_id`/`estado_cumplimiento` por item.
2. Agregar `documento_id` a la serialización del checklist en `ProcesoResource`.
3. Actualizar tipos TS y ambos `show.tsx` para enlazar a la descarga cuando `documento_id` no es null.
4. Tests: sin documento → `pendiente`; con documento sin validar → `cargado`; con documento validado/rechazado → ese estado; múltiples documentos del mismo tipo → gana el más reciente.

Sin cambios de esquema, sin rollback especial.
