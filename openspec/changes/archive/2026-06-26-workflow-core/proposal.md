## Why

La tarea 5 del harness (`tasks/05_implementar_workflow_core.md`) requiere el motor de workflow propio que va a gobernar todos los módulos funcionales futuros (Pago de Proveedores, Adquisiciones, etc.). El harness es explícito: "Workflow antes que CRUD" — ningún cambio de estado puede pasar por fuera de `WorkflowTransitionService::execute()`. Hoy no existe ningún mecanismo de workflow; los módulos funcionales (tareas 6+) no tienen sobre qué construirse.

## What Changes

- 7 migraciones: `workflow_definitions`, `workflow_states`, `workflow_transitions`, `processes`, `workflow_tasks`, `workflow_task_assignments`, `workflow_transition_logs`.
- Tabla estándar `notifications` de Laravel (`php artisan make:notifications-table`) para notificar a responsables.
- `WorkflowTransitionService::execute()`: valida módulo activo (`workflow_definitions.activo`), proceso no cerrado, transición permitida desde el estado actual, permiso del usuario (Spatie, tarea 3), comentario requerido, documentos obligatorios; ejecuta el cambio de estado, cierra tareas asociadas a la transición, registra historial (`workflow_transition_logs`), **audita vía `AuditLogger`** (conecta el escenario pendiente de la tarea 3) y notifica a los responsables de las tareas abiertas.
- `processes` es polimórfico (`subject_type`/`subject_id`) — todavía no hay un módulo de negocio real (eso es la tarea 8); cualquier módulo futuro adjunta su propio modelo como "subject".
- Documentos obligatorios: checklist simple (`processes.documentos_adjuntos` vs `workflow_transitions.documentos_requeridos`, ambos JSON de códigos) — no se construye el expediente documental completo (tarea 6) todavía; esto se reemplaza/extiende cuando esa tarea llegue.

## Capabilities

### New Capabilities

- `workflow-core`: formaliza el spec libre existente (`openspec/specs/workflow-core/spec.md`) al formato estructurado de OpenSpec.

### Modified Capabilities

- `seguridad-auditoria`: el escenario "Auditar cambio de estado de workflow" pasa de pendiente de conexión a conectado — `WorkflowTransitionService::execute()` ya llama a `AuditLogger`.

## Impact

- 8 migraciones nuevas (7 de workflow + `notifications`).
- 7 modelos nuevos, 1 servicio (`WorkflowTransitionService`), 1 excepción (`WorkflowTransitionException`), 1 notificación (`WorkflowTransitionNotification`).
- No afecta ninguna tabla de las tareas 1-4. Los tests usan `Funcionario` (tarea 2) como "subject" de prueba genérico, sin acoplar workflow-core a ningún módulo real.
