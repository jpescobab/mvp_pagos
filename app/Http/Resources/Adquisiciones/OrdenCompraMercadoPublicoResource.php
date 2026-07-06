<?php

namespace App\Http\Resources\Adquisiciones;

use App\Models\OrdenCompraMercadoPublico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrdenCompraMercadoPublico */
class OrdenCompraMercadoPublicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'estado_mercado_publico' => $this->estado_mercado_publico,
            'moneda' => $this->moneda,
            'forma_pago' => $this->forma_pago,
            'plazo_entrega_dias' => $this->plazo_entrega_dias,
            'monto_neto' => $this->monto_neto,
            'monto_total' => $this->monto_total,
            'fecha_emision' => $this->fecha_emision,
            'organismo_comprador' => $this->organismo_comprador,
            'cronograma' => $this->cronograma ?? [],
            'proveedor' => $this->when($this->proveedor !== null, fn () => [
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
                'rutproveedor' => $this->proveedor->rutproveedor,
            ]),
            'proceso_adquisicion' => $this->when($this->procesoAdquisicion !== null, fn () => [
                'id' => $this->procesoAdquisicion->id,
                'codigo' => $this->procesoAdquisicion->codigo,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'codigo_producto' => $item->codigo_producto,
                'descripcion' => $item->descripcion,
                'cantidad' => $item->cantidad,
                'precio_unitario' => $item->precio_unitario,
                'monto_total' => $item->monto_total,
            ])),
        ];
    }
}
