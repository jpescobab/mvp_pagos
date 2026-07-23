<?php

namespace App\Http\Resources\Notificaciones;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/** @mixin DatabaseNotification */
class NotificacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->data;

        return [
            'id' => $this->id,
            'descripcion' => $data['descripcion'] ?? null,
            // Tolera el payload viejo de solo códigos: si no hay nombre legible,
            // cae al código del estado.
            'estado_nuevo' => $data['estado_nuevo_nombre'] ?? $data['estado_nuevo'] ?? null,
            'estado_anterior' => $data['estado_anterior_nombre'] ?? $data['estado_anterior'] ?? null,
            'url' => $data['url'] ?? null,
            'leida' => $this->read_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
