<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\EstadoWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EstadoWorkflow */
class EstadoWorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'es_inicial' => $this->es_inicial,
            'es_final' => $this->es_final,
        ];
    }
}
