<?php

namespace App\Http\Resources\Maestros;

use App\Models\Cfinanciero;
use App\Models\Jurisdiccion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Jurisdiccion */
class JurisdiccionResource extends JsonResource
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
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
            'institucion' => [
                'id' => $this->institucion->id,
                'codigo' => $this->institucion->codigo,
                'nombre' => $this->institucion->nombre,
            ],
            'cfinancieros_count' => $this->whenCounted('cfinancieros'),
            'cfinancieros' => $this->whenLoaded(
                'cfinancieros',
                fn () => $this->cfinancieros->map(fn (Cfinanciero $cfinanciero) => [
                    'id' => $cfinanciero->id,
                    'codigo' => $cfinanciero->codigo,
                    'nombre' => $cfinanciero->nombre,
                    'activo' => $cfinanciero->activo,
                ]),
            ),
        ];
    }
}
