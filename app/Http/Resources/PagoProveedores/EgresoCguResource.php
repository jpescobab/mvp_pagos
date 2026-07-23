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
            'id' => $this->id,
            'numero_egreso' => $this->numero_egreso,
            'fecha' => $this->fecha,
            'monto_total' => $this->monto_total,
            'observaciones' => $this->observaciones,
            'periodo' => $this->periodo,
            'generado_automaticamente' => $this->generado_automaticamente,
            'cantidad_casos' => $this->items->count(),
            'cfinanciero' => $this->whenLoaded(
                'cfinanciero',
                fn () => $this->cfinanciero === null ? null : ['nombre' => $this->cfinanciero->nombre],
            ),
            'registrado_por' => $this->whenLoaded('registradoPor', fn () => $this->registradoPor?->name),
            'items' => $this->items->map(fn ($item) => [
                'caso' => [
                    'id' => $item->caso->id,
                    'sgf_id' => $item->caso->sgf_id,
                ],
                'numero' => $item->caso->numero,
                'periodo' => $item->caso->periodo,
                'fecha_sii' => $item->caso->fecha_sii,
                'folio_egreso' => $item->caso->folio_egreso,
                'observacion' => $item->caso->observacion,
                'proveedor' => $item->caso->relationLoaded('proveedor')
                    ? [
                        'nombre' => $item->caso->proveedor?->nombre,
                        'rutproveedor' => $item->caso->proveedor?->rutproveedor,
                    ]
                    : null,
                'estado_actual' => $item->caso->relationLoaded('proceso') && $item->caso->proceso?->estadoActual !== null
                    ? new EstadoWorkflowResource($item->caso->proceso->estadoActual)
                    : null,
                'monto' => $item->monto,
            ])->values(),
        ];
    }
}
