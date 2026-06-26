<?php

namespace App\Notifications;

use App\Models\Process;
use App\Models\WorkflowState;
use Illuminate\Notifications\Notification;

class WorkflowTransitionNotification extends Notification
{
    public function __construct(
        private readonly Process $process,
        private readonly WorkflowState $estadoAnterior,
        private readonly WorkflowState $estadoNuevo,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'process_id' => $this->process->id,
            'estado_anterior' => $this->estadoAnterior->codigo,
            'estado_nuevo' => $this->estadoNuevo->codigo,
        ];
    }
}
