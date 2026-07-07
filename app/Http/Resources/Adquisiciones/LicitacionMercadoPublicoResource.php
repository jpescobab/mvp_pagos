<?php

namespace App\Http\Resources\Adquisiciones;

use App\Models\LicitacionMercadoPublico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LicitacionMercadoPublico */
class LicitacionMercadoPublicoResource extends JsonResource
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
            'estado_mercado_publico' => $this->estado_mercado_publico,
            'codigo_estado_mercado_publico' => $this->codigo_estado_mercado_publico,
            'moneda' => $this->moneda,
            'monto_estimado' => $this->monto_estimado,
            'organismo_comprador' => $this->organismo_comprador,
            'cronograma' => $this->cronograma ?? [],
            'adjudicacion' => $this->adjudicacion,
            'payload_crudo' => $this->whenLoaded('snapshot', fn () => $this->snapshot?->payload_crudo),
            'proceso_adquisicion' => $this->when($this->procesoAdquisicion !== null, fn () => [
                'id' => $this->procesoAdquisicion->id,
                'codigo' => $this->procesoAdquisicion->codigo,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'correlativo' => $item->correlativo,
                'codigo_producto' => $item->codigo_producto,
                'categoria' => $item->categoria,
                'nombre_producto' => $item->nombre_producto,
                'descripcion' => $item->descripcion,
                'unidad_medida' => $item->unidad_medida,
                'cantidad' => $item->cantidad,
                'adjudicacion' => $item->adjudicacion,
            ])),
        ];
    }
}
