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
     * Payload autocontenido, resuelto en el momento de la transición: nombres
     * legibles de los estados (no solo códigos), descripción del proceso y URL
     * de destino. Así el panel de notificaciones se renderiza y navega sin
     * consultas adicionales, y la notificación sigue siendo correcta aunque el
     * proceso avance a otro estado después.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $descriptor = $this->proceso->descriptorNotificacion();

        return [
            'proceso_id' => $this->proceso->id,
            'estado_anterior' => $this->estadoAnterior->codigo,
            'estado_anterior_nombre' => $this->estadoAnterior->nombre,
            'estado_nuevo' => $this->estadoNuevo->codigo,
            'estado_nuevo_nombre' => $this->estadoNuevo->nombre,
            'descripcion' => $descriptor['descripcion'],
            'url' => $descriptor['url'],
        ];
    }
}
