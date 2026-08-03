<?php

namespace App\Http\Resources\Contratos;

use App\Models\ContratoCuota;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ContratoCuota */
class ContratoCuotaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_cuota' => $this->numero_cuota,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'monto' => $this->monto,
            'moneda' => $this->moneda,
            'estado' => $this->estado,
            'esta_vencida' => $this->esta_vencida,
            'caso_pago_proveedor' => $this->whenLoaded(
                'casoPagoProveedor',
                fn () => $this->casoPagoProveedor === null ? null : [
                    'id' => $this->casoPagoProveedor->id,
                    'sgf_id' => $this->casoPagoProveedor->sgf_id,
                ],
            ),
        ];
    }
}
