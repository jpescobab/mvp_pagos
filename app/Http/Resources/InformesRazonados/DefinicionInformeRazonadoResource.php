<?php

namespace App\Http\Resources\InformesRazonados;

use App\Models\DefinicionInformeRazonado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DefinicionInformeRazonado */
class DefinicionInformeRazonadoResource extends JsonResource
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
            'ejecuciones_count' => $this->whenCounted('ejecuciones'),
        ];
    }
}
