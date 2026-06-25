# Tarea 05 — Workflow Core

Implementar workflow propio sobre Laravel.

Tablas:

- workflow_definitions
- workflow_states
- workflow_transitions
- processes
- workflow_tasks
- workflow_task_assignments
- workflow_transition_logs

Servicio obligatorio:

- WorkflowTransitionService::execute()

Debe validar permisos, documentos, estado actual, transición, tareas, auditoría y notificaciones.
