# AGENTS.md — Instrucciones obligatorias para agentes IA

Antes de modificar código, leer:

1. `HARNESS_IA.md`
2. `openspec/config.yaml` (sección `context:`, resumen del harness inyectado en todo artefacto OpenSpec)
3. El spec correspondiente en `openspec/specs/*/spec.md`
4. La tarea correspondiente en `tasks/*.md`

## Reglas críticas

- No inventar arquitectura fuera del harness.
- No convertir procesos institucionales en CRUDs planos.
- No usar estados/grupos SGF como estados/grupos internos.
- No consultar APIs externas desde React.
- No cambiar estados fuera de `WorkflowTransitionService`.
- No borrar snapshots, documentos ni auditoría.
- No usar Playwright para evadir MFA, CAPTCHA o controles.
- Todo dato externo usado en gestión, cálculo o informe debe tener snapshot.
- No dejar lógica de negocio en controladores (queries de negocio, `DB::transaction`, ramas condicionales, service locator): extraer a un Service al escribir `tasks.md` y al aplicar, no en una auditoría posterior (ver `HARNESS_IA.md` §15, "Disciplina de controladores livianos en OpenSpec").

## Stack obligatorio

Laravel 13, PostgreSQL, React, Laravel Boost, Spatie Permission, Queue, Scheduler, Process, OpenSpec y Playwright autorizado cuando corresponda.
