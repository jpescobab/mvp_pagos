<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\DetalleFactura;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DetalleFactura */
class DetalleFacturaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'factura_id' => $this->factura_id,
            'tipo_compra_id' => $this->tipo_compra_id,
            'ccosto_id' => $this->ccosto_id,
            'cantidad' => $this->cantidad,
            'unidad_medida' => $this->unidad_medida,
            'monto' => $this->monto,
            'tipo_compra' => $this->whenLoaded(
                'tipoCompra',
                fn () => [
                    'id' => $this->tipoCompra->id,
                    'nombre' => $this->tipoCompra->nombre,
                ],
            ),
            'ccosto' => $this->whenLoaded(
                'ccosto',
                fn () => [
                    'id' => $this->ccosto->id,
                    'codigo' => $this->ccosto->codigo,
                    'nombre' => $this->ccosto->nombre,
                ],
            ),
            'factura' => $this->whenLoaded(
                'factura',
                fn () => [
                    'id' => $this->factura->id,
                    'folio' => $this->factura->folio,
                    'monto' => $this->factura->monto,
                ],
            ),
        ];
    }
}
