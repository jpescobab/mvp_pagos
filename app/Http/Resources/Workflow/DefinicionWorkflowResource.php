<?php

namespace App\Http\Resources\Workflow;

use App\Models\DefinicionWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\TransicionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DefinicionWorkflow */
class DefinicionWorkflowResource extends JsonResource
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
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
            'estados_count' => $this->whenCounted('estados'),
            'transiciones_count' => $this->whenCounted('transiciones'),
            'estados' => $this->whenLoaded('estados', fn () => $this->mapEstados()),
            'transiciones' => $this->whenLoaded('transiciones', fn () => $this->mapTransiciones()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapEstados(): array
    {
        return array_values($this->estados
            ->map(fn (EstadoWorkflow $estado) => [
                'id' => $estado->id,
                'codigo' => $estado->codigo,
                'nombre' => $estado->nombre,
                'es_inicial' => $estado->es_inicial,
                'es_final' => $estado->es_final,
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapTransiciones(): array
    {
        return array_values($this->transiciones
            ->map(fn (TransicionWorkflow $transicion) => [
                'id' => $transicion->id,
                'codigo' => $transicion->codigo,
                'nombre' => $transicion->nombre,
                'estado_origen' => $transicion->estadoOrigen->codigo,
                'estado_destino' => $transicion->estadoDestino->codigo,
                'permiso_requerido' => $transicion->permiso_requerido,
                'documentos_requeridos' => $transicion->documentos_requeridos,
                'requiere_comentario' => $transicion->requiere_comentario,
            ])
            ->all());
    }
}
