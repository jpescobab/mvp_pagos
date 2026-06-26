<?php

namespace App\Services\Workflow;

use App\Exceptions\TransicionWorkflowException;
use App\Models\AsignacionTareaWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\Proceso;
use App\Models\TransicionWorkflow;
use App\Models\User;
use App\Notifications\TransicionWorkflowNotification;
use App\Services\AuditLogger;
use App\Services\Documentos\ResolutorValidacionDocumental;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TransicionWorkflowService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly ResolutorValidacionDocumental $resolutorValidacionDocumental,
    ) {}

    /**
     * The single authorized entry point to change a process's state. No
     * controller, job, seeder or React component may change a process's
     * state any other way.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        Proceso $proceso,
        string $transitionCodigo,
        ?string $comentario = null,
        array $metadata = [],
        ?User $user = null,
    ): Proceso {
        $user ??= Auth::user();

        $proceso->loadMissing('definicionWorkflow', 'estadoActual');

        if (! $proceso->definicionWorkflow->activo) {
            throw TransicionWorkflowException::moduloInactivo();
        }

        if ($proceso->cerrado_en !== null) {
            throw TransicionWorkflowException::procesoCerrado();
        }

        $transicion = TransicionWorkflow::where('definicion_workflow_id', $proceso->definicion_workflow_id)
            ->where('estado_origen_id', $proceso->estado_actual_id)
            ->where('codigo', $transitionCodigo)
            ->first();

        if ($transicion === null) {
            throw TransicionWorkflowException::transicionNoPermitida($transitionCodigo);
        }

        if ($transicion->permiso_requerido !== null && ($user === null || ! $user->can($transicion->permiso_requerido))) {
            throw TransicionWorkflowException::sinPermiso($transicion->permiso_requerido);
        }

        if ($transicion->requiere_comentario && trim((string) $comentario) === '') {
            throw TransicionWorkflowException::comentarioRequerido();
        }

        $faltantes = $this->resolutorValidacionDocumental->faltantes(
            $proceso,
            $transicion->documentos_requeridos ?? [],
        );

        if ($faltantes !== []) {
            throw TransicionWorkflowException::documentosFaltantes($faltantes);
        }

        return DB::transaction(function () use ($proceso, $transicion, $comentario, $metadata, $user) {
            $estadoAnterior = $proceso->estadoActual;
            $estadoNuevo = $transicion->estadoDestino;

            $proceso->update([
                'estado_actual_id' => $estadoNuevo->id,
                'cerrado_en' => $estadoNuevo->es_final ? now() : null,
            ]);

            $proceso->historialTransiciones()->create([
                'transicion_workflow_id' => $transicion->id,
                'estado_origen_id' => $estadoAnterior->id,
                'estado_destino_id' => $estadoNuevo->id,
                'user_id' => $user?->id,
                'comentario' => $comentario,
                'metadata' => $metadata,
            ]);

            $proceso->tareas()
                ->where('transicion_workflow_id', $transicion->id)
                ->where('estado', 'pendiente')
                ->update(['estado' => 'completada']);

            $this->auditLogger->log(
                action: 'workflow.transicion',
                auditable: $proceso,
                before: ['estado' => $estadoAnterior->codigo],
                after: ['estado' => $estadoNuevo->codigo],
                metadata: [...$metadata, 'transicion' => $transicion->codigo, 'comentario' => $comentario],
                user: $user,
            );

            $this->notificarResponsables($proceso, $estadoAnterior, $estadoNuevo);

            return $proceso->refresh();
        });
    }

    private function notificarResponsables(Proceso $proceso, EstadoWorkflow $estadoAnterior, EstadoWorkflow $estadoNuevo): void
    {
        $userIds = AsignacionTareaWorkflow::whereHas(
            'tareaWorkflow',
            fn ($query) => $query->where('proceso_id', $proceso->id),
        )->pluck('user_id')->unique();

        if ($userIds->isEmpty()) {
            return;
        }

        $usuarios = User::whereIn('id', $userIds)->get();

        Notification::send($usuarios, new TransicionWorkflowNotification($proceso, $estadoAnterior, $estadoNuevo));
    }
}
