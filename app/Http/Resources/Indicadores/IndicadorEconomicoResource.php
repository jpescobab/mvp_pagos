<?php

namespace App\Http\Resources\Indicadores;

use App\Models\IndicadorEconomico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin IndicadorEconomico */
class IndicadorEconomicoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'fecha_valor' => $this->fecha_valor,
            'periodo' => $this->periodo,
            'valor' => $this->valor,
            'fuente' => $this->fuente,
            'vigente_desde' => $this->vigente_desde,
            'vigente_hasta' => $this->vigente_hasta,
        ];
    }
}
