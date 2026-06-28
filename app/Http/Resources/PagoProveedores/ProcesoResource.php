<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\Proceso;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Proceso */
class ProcesoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado_actual' => new EstadoWorkflowResource($this->estadoActual),
            'cerrado_en' => $this->cerrado_en,
            'historial_transiciones' => HistorialTransicionResource::collection($this->whenLoaded('historialTransiciones')),
            'transiciones_disponibles' => TransicionWorkflowResource::collection(
                $this->definicionWorkflow->transiciones
                    ->where('estado_origen_id', $this->estado_actual_id)
                    ->values(),
            ),
            'checklist' => $this->whenLoaded('checklist', fn () => $this->checklist === null ? null : [
                'items' => $this->checklist->items->map(fn ($item) => [
                    'tipo_documento' => $item->tipoDocumento?->nombre,
                    'tipo_requisito' => $item->tipo_requisito,
                    'estado_cumplimiento' => $item->estado_cumplimiento,
                ])->values(),
            ]),
            'documentos' => $this->whenLoaded(
                'vinculosDocumento',
                fn () => $this->vinculosDocumento
                    ->where('activo', true)
                    ->map(fn ($vinculo) => [
                        'vinculo_id' => $vinculo->id,
                        'documento_id' => $vinculo->documento->id,
                        'tipo_documento' => $vinculo->documento->tipoDocumento?->nombre,
                        'nombre_archivo' => $vinculo->documento->versiones->last()?->nombre_archivo,
                        'estado_vigente' => $vinculo->documento->estadoVigente(),
                    ])->values(),
            ),
        ];
    }
}
