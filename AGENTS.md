# AGENTS.md — Instrucciones obligatorias para agentes IA

Antes de modificar código, leer:

1. `HARNESS_IA.md`
2. `openspec/project.md`
3. `openspec/principles.md`
4. El spec correspondiente en `openspec/specs/*/spec.md`
5. La tarea correspondiente en `tasks/*.md`

## Reglas críticas

- No inventar arquitectura fuera del harness.
- No convertir procesos institucionales en CRUDs planos.
- No usar estados/grupos SGF como estados/grupos internos.
- No consultar APIs externas desde React.
- No cambiar estados fuera de `WorkflowTransitionService`.
- No borrar snapshots, documentos ni auditoría.
- No usar Playwright para evadir MFA, CAPTCHA o controles.
- Todo dato externo usado en gestión, cálculo o informe debe tener snapshot.

## Stack obligatorio

Laravel 13, PostgreSQL, React, Laravel Boost, Spatie Permission, Queue, Scheduler, Process, OpenSpec y Playwright autorizado cuando corresponda.
