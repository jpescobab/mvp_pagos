<?php

namespace App\Http\Resources\Sgf;

use App\Models\SnapshotDatosExterno;
use App\Models\TrabajoIntegracion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TrabajoIntegracion */
class ImportacionSgfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'mecanismo' => $this->mecanismo,
            'iniciado_por' => $this->iniciadoPor?->name,
            'iniciado_en' => $this->iniciado_en,
            'finalizado_en' => $this->finalizado_en,
            'total_elementos' => $this->total_elementos,
            'estado' => $this->estado,
            'error' => $this->error,
            'snapshots' => $this->whenLoaded('snapshotsDatosExternos', fn () => $this->mapSnapshots()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapSnapshots(): array
    {
        return array_values($this->snapshotsDatosExternos
            ->map(fn (SnapshotDatosExterno $snapshot) => [
                'id' => $snapshot->id,
                'referencia_externa' => $snapshot->referencia_externa,
                'hash' => $snapshot->hash,
                'capturado_en' => $snapshot->capturado_en,
            ])
            ->all());
    }
}
