## 1. Migraciones

- [x] 1.1 `create_workflow_definitions_table`: id, codigo (unique), nombre, descripcion (nullable), activo (boolean default true), timestamps.
- [x] 1.2 `create_workflow_states_table`: id, workflow_definition_id (FK restrict), codigo, nombre, es_inicial (boolean default false), es_final (boolean default false), timestamps; unique(workflow_definition_id, codigo).
- [x] 1.3 `create_workflow_transitions_table`: id, workflow_definition_id (FK restrict), from_state_id (FK -> workflow_states restrict), to_state_id (FK -> workflow_states restrict), codigo, nombre, permiso_requerido (nullable), documentos_requeridos (json nullable), requiere_comentario (boolean default false), timestamps; unique(workflow_definition_id, codigo).
- [x] 1.4 `create_processes_table`: id, workflow_definition_id (FK restrict), current_state_id (FK -> workflow_states restrict), subject_type, subject_id, documentos_adjuntos (json nullable), iniciado_por (nullable FK -> users, nullOnDelete), cerrado_en (nullable timestamp), timestamps; índice (subject_type, subject_id).
- [x] 1.5 `create_workflow_tasks_table`: id, process_id (FK restrict), workflow_transition_id (nullable FK -> workflow_transitions, nullOnDelete), titulo, descripcion (nullable), estado (default 'pendiente'), vence_en (nullable date), timestamps.
- [x] 1.6 `create_workflow_task_assignments_table`: id, workflow_task_id (FK restrict), user_id (FK restrict), asignado_en (timestamp useCurrent); unique(workflow_task_id, user_id).
- [x] 1.7 `create_workflow_transition_logs_table`: id, process_id (FK restrict), workflow_transition_id (nullable FK, nullOnDelete), from_state_id (FK restrict), to_state_id (FK restrict), user_id (nullable FK nullOnDelete), comentario (nullable text), metadata (json nullable), created_at (sin updated_at).
- [x] 1.8 `php artisan make:notifications-table` (tabla estándar de Laravel).

## 2. Modelos

- [x] 2.1 `WorkflowDefinition` (hasMany states, transitions, processes).
- [x] 2.2 `WorkflowState` (belongsTo definition; relaciones inversas de transición no necesarias como métodos).
- [x] 2.3 `WorkflowTransition` (belongsTo definition, fromState, toState).
- [x] 2.4 `Process` (belongsTo definition, currentState; morphTo subject; hasMany tasks, transitionLogs).
- [x] 2.5 `WorkflowTask` (belongsTo process, transition nullable; hasMany assignments).
- [x] 2.6 `WorkflowTaskAssignment` (belongsTo task, user).
- [x] 2.7 `WorkflowTransitionLog` (belongsTo process, transition nullable, fromState, toState, user nullable).
- [x] 2.8 PHPDoc genérico en todas las relaciones para que PHPStan/Larastan pase sin errores.

## 3. Servicio de transición

- [x] 3.1 `app/Exceptions/WorkflowTransitionException.php`.
- [x] 3.2 `app/Notifications/WorkflowTransitionNotification.php` (`toDatabase()`).
- [x] 3.3 `app/Services/Workflow/WorkflowTransitionService.php::execute(Process $process, string $transitionCodigo, ?string $comentario, array $metadata, ?User $user)`: valida módulo activo, proceso no cerrado, transición permitida desde el estado actual, permiso (Spatie), comentario requerido, documentos requeridos.
- [x] 3.4 Ejecuta en transacción: cambia `current_state_id`, marca `cerrado_en` si el nuevo estado es final, crea `workflow_transition_logs`, cierra `workflow_tasks` vinculadas a la transición, llama `AuditLogger::log()`, notifica a los responsables de tareas todavía abiertas.

## 4. Conectar auditoría (tarea 3)

- [x] 4.1 Verificar que `AuditLogger::log()` recibe los datos correctos desde `WorkflowTransitionService` (acción, proceso, estado anterior/nuevo, comentario, metadata).

## 5. Tests

- [x] 5.1 Test: ejecutar una transición válida cambia el estado, crea el log, cierra las tareas asociadas, audita y notifica a los responsables.
- [x] 5.2 Test: bloquea si falta un documento requerido y no modifica el estado.
- [x] 5.3 Test: bloquea si el usuario no tiene el permiso requerido por la transición.
- [x] 5.4 Test: bloquea si la transición no es válida desde el estado actual del proceso.
- [x] 5.5 Test: bloquea cualquier transición si `workflow_definitions.activo = false`.
- [x] 5.6 Test: bloquea si la transición exige comentario y no se proporciona.
- [x] 5.7 Test: crear un proceso lo deja en el estado `es_inicial` de su workflow.

## 6. Validación

- [x] 6.1 Ejecutar `php artisan migrate` contra PostgreSQL.
- [x] 6.2 Ejecutar `composer test` (Pint + PHPStan + Pest) y `npm run lint:check`/`types:check`, todo en verde.
