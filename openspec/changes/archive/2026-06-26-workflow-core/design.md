## Context

Esta es la pieza arquitectónica más importante hasta ahora: el harness exige "workflow antes que CRUD" — todo módulo funcional futuro (Pago de Proveedores, Adquisiciones, etc.) debe gobernar sus estados exclusivamente a través de `WorkflowTransitionService::execute()`. Hoy no existe ningún módulo de negocio real (eso llega en tareas 6-10), así que `processes` debe ser genérico desde el día uno.

## Goals / Non-Goals

**Goals:**
- Un motor de workflow reusable: definiciones, estados, transiciones, procesos, tareas y su historial.
- `WorkflowTransitionService::execute()` como único punto de cambio de estado, con todas las validaciones del harness (módulo activo, permisos, documentos, comentario).
- Cerrar el loop de auditoría dejado pendiente en la tarea 3.
- Notificar a responsables usando el mecanismo estándar de Laravel, no algo custom.

**Non-Goals:**
- No se construye el expediente documental completo (tarea 6) — el checklist de documentos requeridos es una lista simple de códigos por ahora.
- No se construye `system_modules` como tabla separada — `workflow_definitions.activo` cumple el rol de "módulo activo" para esta tarea.
- No se crean tareas automáticamente al entrar a un estado — eso es lógica específica de cada módulo futuro; el motor solo sabe **cerrar** tareas ya asociadas a la transición que se ejecuta.
- No se construye ningún módulo de negocio real ni su UI — los tests usan `Funcionario` (tarea 2) como "subject" polimórfico de prueba.

## Decisions

- **`processes.subject_type`/`subject_id` polimórfico**, en vez de una FK fija a una tabla de negocio. Es la única forma de que workflow-core sirva para Pago de Proveedores, Adquisiciones, etc. sin reescribirse en cada tarea futura.
- **Checklist de documentos simple (JSON de códigos)** en vez de modelar `document_types`/`documents` ahora. Cuando llegue la tarea 6, esa tarea decide si remplaza esta comparación por una consulta real al expediente — no se anticipa esa estructura hoy.
- **El motor solo cierra tareas, no las crea.** "Cierra o crea tareas según corresponda" (HARNESS_IA) se interpreta como: cerrar las tareas (`workflow_tasks`) ya vinculadas a la transición ejecutada (`workflow_transition_id`). Crear tareas nuevas es decisión de negocio de cada módulo (qué tarea, para quién, con qué plazo) — no algo que el motor genérico pueda inventar sin ese contexto.
- **Notificaciones vía la tabla estándar de Laravel** (`php artisan make:notifications-table`), no una tabla custom. Se notifica a todos los usuarios con alguna asignación en tareas del proceso (abiertas o recién cerradas por la transición) — es el único concepto de "responsable" que workflow-core tiene por sí mismo. Notificar también a quienes tenían la tarea recién cerrada es intencional: les informa el resultado de lo que acaban de resolver.
- **`WorkflowTransitionException`** dedicada para todos los bloqueos (módulo inactivo, transición no permitida, sin permiso, comentario requerido, documento faltante) — permite a quien llame distinguir "se bloqueó la transición" de un error de programación real.
- **Auditoría conecta con `AuditLogger` de la tarea 3** — cierra el escenario que quedó explícitamente marcado como "pendiente de conexión" en `seguridad-auditoria`.

## Risks / Trade-offs

- **[Riesgo] El checklist de documentos simple no valida que el documento sea válido/vigente**, solo que el código esté presente → Mitigación: aceptado — es exactamente el alcance de esta tarea; la tarea 6 endurece esto cuando exista expediente documental real.
- **[Riesgo] Sin creación automática de tareas, un módulo futuro podría "olvidar" crear las tareas necesarias** → Mitigación: aceptado como decisión de diseño — el motor no puede adivinar la lógica de negocio de un módulo que no existe todavía; cada módulo es responsable de crear sus propias `workflow_tasks`.
