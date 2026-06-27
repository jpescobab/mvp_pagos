<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\EgresoCgu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EgresoCgu */
class EgresoCguResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'numero_egreso' => $this->numero_egreso,
            'fecha' => $this->fecha,
            'monto_total' => $this->monto_total,
            'observaciones' => $this->observaciones,
            'items' => $this->items->map(fn ($item) => [
                'caso' => ['sgf_id' => $item->caso->sgf_id],
                'monto' => $item->monto,
            ])->values(),
        ];
    }
}
