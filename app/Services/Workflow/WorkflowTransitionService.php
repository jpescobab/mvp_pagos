<?php

namespace App\Services\Workflow;

use App\Exceptions\WorkflowTransitionException;
use App\Models\Process;
use App\Models\User;
use App\Models\WorkflowState;
use App\Models\WorkflowTaskAssignment;
use App\Models\WorkflowTransition;
use App\Notifications\WorkflowTransitionNotification;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class WorkflowTransitionService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * The single authorized entry point to change a process's state. No
     * controller, job, seeder or React component may change a process's
     * state any other way.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        Process $process,
        string $transitionCodigo,
        ?string $comentario = null,
        array $metadata = [],
        ?User $user = null,
    ): Process {
        $user ??= Auth::user();

        $process->loadMissing('workflowDefinition', 'currentState');

        if (! $process->workflowDefinition->activo) {
            throw WorkflowTransitionException::moduloInactivo();
        }

        if ($process->cerrado_en !== null) {
            throw WorkflowTransitionException::procesoCerrado();
        }

        $transition = WorkflowTransition::where('workflow_definition_id', $process->workflow_definition_id)
            ->where('from_state_id', $process->current_state_id)
            ->where('codigo', $transitionCodigo)
            ->first();

        if ($transition === null) {
            throw WorkflowTransitionException::transicionNoPermitida($transitionCodigo);
        }

        if ($transition->permiso_requerido !== null && ($user === null || ! $user->can($transition->permiso_requerido))) {
            throw WorkflowTransitionException::sinPermiso($transition->permiso_requerido);
        }

        if ($transition->requiere_comentario && trim((string) $comentario) === '') {
            throw WorkflowTransitionException::comentarioRequerido();
        }

        $faltantes = array_values(array_diff(
            $transition->documentos_requeridos ?? [],
            $process->documentos_adjuntos ?? [],
        ));

        if ($faltantes !== []) {
            throw WorkflowTransitionException::documentosFaltantes($faltantes);
        }

        return DB::transaction(function () use ($process, $transition, $comentario, $metadata, $user) {
            $estadoAnterior = $process->currentState;
            $estadoNuevo = $transition->toState;

            $process->update([
                'current_state_id' => $estadoNuevo->id,
                'cerrado_en' => $estadoNuevo->es_final ? now() : null,
            ]);

            $process->transitionLogs()->create([
                'workflow_transition_id' => $transition->id,
                'from_state_id' => $estadoAnterior->id,
                'to_state_id' => $estadoNuevo->id,
                'user_id' => $user?->id,
                'comentario' => $comentario,
                'metadata' => $metadata,
            ]);

            $process->tasks()
                ->where('workflow_transition_id', $transition->id)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'completada']);

            $this->auditLogger->log(
                action: 'workflow.transicion',
                auditable: $process,
                before: ['estado' => $estadoAnterior->codigo],
                after: ['estado' => $estadoNuevo->codigo],
                metadata: [...$metadata, 'transicion' => $transition->codigo, 'comentario' => $comentario],
                user: $user,
            );

            $this->notificarResponsables($process, $estadoAnterior, $estadoNuevo);

            return $process->refresh();
        });
    }

    private function notificarResponsables(Process $process, WorkflowState $estadoAnterior, WorkflowState $estadoNuevo): void
    {
        $userIds = WorkflowTaskAssignment::whereHas(
            'workflowTask',
            fn ($query) => $query->where('process_id', $process->id),
        )->pluck('user_id')->unique();

        if ($userIds->isEmpty()) {
            return;
        }

        $usuarios = User::whereIn('id', $userIds)->get();

        Notification::send($usuarios, new WorkflowTransitionNotification($process, $estadoAnterior, $estadoNuevo));
    }
}
