<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\EgresoCgu;
use App\Models\VinculoDocumento;
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
            'items' => $this->items->map(fn ($item) => [
                'caso' => ['sgf_id' => $item->caso->sgf_id],
                'monto' => $item->monto,
            ])->values(),
            'documentos' => $this->whenLoaded(
                'vinculosDocumento',
                fn () => $this->mapDocumentosVinculados(),
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapDocumentosVinculados(): array
    {
        return array_values($this->vinculosDocumento
            ->where('activo', true)
            ->map(fn (VinculoDocumento $vinculo) => [
                'vinculo_id' => $vinculo->id,
                'documento_id' => $vinculo->documento->id,
                'tipo_documento' => $vinculo->documento->tipoDocumento?->nombre,
                'nombre_archivo' => $vinculo->documento->versiones->last()?->nombre_archivo,
                'estado_vigente' => $vinculo->documento->estadoVigente(),
                'validaciones' => [],
            ])
            ->all());
    }
}
