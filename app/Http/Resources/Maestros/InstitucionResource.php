<?php

namespace App\Http\Resources\Maestros;

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Institucion */
class InstitucionResource extends JsonResource
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
            'jurisdicciones_count' => $this->whenCounted('jurisdicciones'),
            'jurisdicciones' => $this->whenLoaded(
                'jurisdicciones',
                fn () => $this->jurisdicciones->map(fn (Jurisdiccion $jurisdiccion) => [
                    'id' => $jurisdiccion->id,
                    'codigo' => $jurisdiccion->codigo,
                    'nombre' => $jurisdiccion->nombre,
                    'activo' => $jurisdiccion->activo,
                ]),
            ),
        ];
    }
}
