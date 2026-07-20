<?php

namespace App\Http\Resources\PagoProveedores;

use App\Models\Documento;
use App\Models\Proceso;
use App\Models\VinculoDocumento;
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
            'tipo_proceso_pago_id' => $this->tipo_proceso_pago_id,
            'tipo_proceso_pago' => $this->whenLoaded('tipoProcesoPago', fn () => $this->tipoProcesoPago === null ? null : [
                'id' => $this->tipoProcesoPago->id,
                'codigo' => $this->tipoProcesoPago->codigo,
                'nombre' => $this->tipoProcesoPago->nombre,
                'requiere_traspaso_cgu' => $this->tipoProcesoPago->requiere_traspaso_cgu,
            ]),
            'historial_transiciones' => HistorialTransicionResource::collection($this->whenLoaded('historialTransiciones')),
            'transiciones_disponibles' => TransicionWorkflowResource::collection(
                $this->definicionWorkflow->transiciones
                    ->where('estado_origen_id', $this->estado_actual_id)
                    ->values(),
            ),
            'checklist' => $this->whenLoaded('checklist', fn () => $this->checklist === null ? null : [
                'items' => $this->checklist->items->map(fn ($item) => [
                    'tipo_documento' => $item->tipoDocumento?->nombre,
                    'tipo_documento_id' => $item->tipo_documento_id,
                    'tipo_requisito' => $item->tipo_requisito,
                    'estado_cumplimiento' => $item->estado_cumplimiento,
                    'documento_id' => $item->documento_id,
                ])->values(),
            ]),
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
        $tiposEnChecklist = $this->checklist?->items
            ->pluck('tipo_documento_id')
            ->filter()
            ->all() ?? [];

        return array_values($this->vinculosDocumento
            ->where('activo', true)
            ->map(fn (VinculoDocumento $vinculo) => [
                'vinculo_id' => $vinculo->id,
                'documento_id' => $vinculo->documento->id,
                'tipo_documento' => $vinculo->documento->tipoDocumento?->nombre,
                'tipo_documento_id' => $vinculo->documento->tipo_documento_id,
                'coincide_checklist' => in_array($vinculo->documento->tipo_documento_id, $tiposEnChecklist, true),
                'nombre_archivo' => $vinculo->documento->versiones->last()?->nombre_archivo,
                'estado_vigente' => $vinculo->documento->estadoVigente(),
                'validaciones' => $this->mapHistorialValidaciones($vinculo->documento),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapHistorialValidaciones(Documento $documento): array
    {
        return array_values($documento->validaciones
            ->sortByDesc('id')
            ->map(fn ($validacion) => [
                'estado' => $validacion->estado,
                'observacion' => $validacion->observacion,
                'validado_por' => $validacion->validadoPor?->name,
                'validado_en' => $validacion->validado_en,
            ])
            ->all());
    }
}
