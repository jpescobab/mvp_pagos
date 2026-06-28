## Context

`ValidacionDocumento` ya tiene todo lo necesario (`estado`, `observacion`, `validado_por`, `validado_en`, inmutable). `Documento::estadoVigente()` ya calcula el estado vigente leyendo `$this->validaciones->sortByDesc('id')->first()`. El historial completo simplemente nunca se serializó hacia el frontend.

## Goals / Non-Goals

**Goals:**
- Exponer el historial completo de validaciones de cada documento vinculado, ordenado del más reciente al más antiguo.
- Mostrarlo en la UI de forma que la observación de un rechazo pasado siga siendo visible después de una corrección.

**Non-Goals:**
- No se agrega ninguna acción nueva (editar/eliminar un evento de validación) — siguen siendo inmutables, solo de lectura.
- No se cambia el cálculo de `estadoVigente()`.

## Decisions

**D1 — Reutilizar la relación `documento.validaciones` ya cargada, ordenarla en el Resource.**
En vez de una query nueva, se mapea `$vinculo->documento->validaciones` (ya cargada vía eager loading) ordenada por `id` descendente — mismo criterio que ya usa `estadoVigente()` internamente, así que ambos coinciden sobre cuál es "la más reciente".

**D2 — `validado_por` se expone como nombre, no como id.**
Mismo patrón que `historial_transiciones` (`item.user.name`) — el frontend nunca necesita el id del usuario, solo mostrarlo.

## Risks / Trade-offs

- [Riesgo] Ninguno relevante — es un cambio de solo lectura sobre datos que ya existen y ya se persisten correctamente.

## Migration Plan

1. Agregar `validaciones` al mapeo de `documentos` en `ProcesoResource`.
2. Extender el eager loading en ambos controladores.
3. UI + tipos TS.
4. Tests.

Sin cambios de esquema, sin rollback especial.
