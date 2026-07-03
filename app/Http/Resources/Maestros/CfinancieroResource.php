<?php

namespace App\Http\Resources\Maestros;

use App\Models\Cfinanciero;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cfinanciero */
class CfinancieroResource extends JsonResource
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
            'activo' => $this->activo,
            'jurisdiccion' => [
                'id' => $this->jurisdiccion->id,
                'nombre' => $this->jurisdiccion->nombre,
            ],
        ];
    }
}
