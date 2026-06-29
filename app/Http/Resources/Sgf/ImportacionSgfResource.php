<?php

namespace App\Http\Resources\Sgf;

use App\Models\ImportacionSgf;
use App\Models\SnapshotSgf;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ImportacionSgf */
class ImportacionSgfResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fuente' => $this->fuente,
            'iniciado_por' => $this->iniciadoPor?->name,
            'iniciado_en' => $this->iniciado_en,
            'finalizado_en' => $this->finalizado_en,
            'total_filas' => $this->total_filas,
            'estado' => $this->estado,
            'snapshots' => $this->whenLoaded('snapshots', fn () => $this->mapSnapshots()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapSnapshots(): array
    {
        return array_values($this->snapshots
            ->map(fn (SnapshotSgf $snapshot) => [
                'id' => $snapshot->id,
                'sgf_id' => $snapshot->sgf_id,
                'hash' => $snapshot->hash,
                'capturado_en' => $snapshot->capturado_en,
            ])
            ->all());
    }
}
