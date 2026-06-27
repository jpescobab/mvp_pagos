<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\HistorialTransicionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HistorialTransicionWorkflow */
class HistorialTransicionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'transicion' => [
                'codigo' => $this->transicion->codigo,
                'nombre' => $this->transicion->nombre,
            ],
            'estado_origen' => ['codigo' => $this->estadoOrigen->codigo],
            'estado_destino' => ['codigo' => $this->estadoDestino->codigo],
            'user' => ['name' => $this->user?->name],
            'comentario' => $this->comentario,
            'created_at' => $this->created_at,
        ];
    }
}
