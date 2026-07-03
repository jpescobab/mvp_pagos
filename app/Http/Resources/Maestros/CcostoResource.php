<?php

namespace App\Http\Resources\Maestros;

use App\Models\Ccosto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ccosto */
class CcostoResource extends JsonResource
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
            'cod_edificio' => $this->cod_edificio,
            'activo' => $this->activo,
            'cfinanciero' => [
                'id' => $this->cfinanciero->id,
                'nombre' => $this->cfinanciero->nombre,
            ],
        ];
    }
}
