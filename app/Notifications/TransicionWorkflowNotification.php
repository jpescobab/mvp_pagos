<?php

namespace App\Notifications;

use App\Models\EstadoWorkflow;
use App\Models\Proceso;
use Illuminate\Notifications\Notification;

class TransicionWorkflowNotification extends Notification
{
    public function __construct(
        private readonly Proceso $proceso,
        private readonly EstadoWorkflow $estadoAnterior,
        private readonly EstadoWorkflow $estadoNuevo,
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
            'proceso_id' => $this->proceso->id,
            'estado_anterior' => $this->estadoAnterior->codigo,
            'estado_nuevo' => $this->estadoNuevo->codigo,
        ];
    }
}
