<?php

namespace App\Http\Resources\Contratos;

use App\Models\ContratoItemConvenioPrecio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ContratoItemConvenioPrecio */
class ContratoItemConvenioPrecioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'unidad_medida' => $this->unidad_medida,
            'precio_unitario' => $this->precio_unitario,
            'moneda' => $this->moneda,
            'vigente_desde' => $this->vigente_desde,
            'vigente_hasta' => $this->vigente_hasta,
        ];
    }
}
