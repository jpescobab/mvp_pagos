<?php

namespace App\Http\Resources\Integraciones;

use App\Models\SistemaExterno;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SistemaExterno */
class SistemaExternoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo_integracion' => $this->tipo_integracion,
            'activo' => $this->activo,
            'trabajos_integracion_count' => $this->whenCounted('trabajosIntegracion'),
        ];
    }
}
