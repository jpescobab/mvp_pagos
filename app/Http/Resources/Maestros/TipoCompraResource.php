<?php

namespace App\Http\Resources\Maestros;

use App\Models\TipoCompra;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TipoCompra */
class TipoCompraResource extends JsonResource
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
        ];
    }
}
