<?php

namespace App\Services\Adquisiciones;

use App\Models\HistorialTransicionWorkflow;
use App\Models\ProcesoAdquisicion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ExportadorSolicitudCompraPdfService
{
    /**
     * Código de la transición que registra la aprobación de la jefatura de
     * la unidad requirente (ver design.md del change
     * `rediseno-solicitud-compra-adquisiciones`: reutiliza `en_revision →
     * publicada` en vez de un mecanismo de aprobación propio).
     */
    private const CODIGO_TRANSICION_APROBACION = 'publicar';

    public function generar(ProcesoAdquisicion $proceso): string
    {
        $proceso->loadMissing([
            'modalidad',
            'ccosto',
            'funcionarioRequirente',
            'proceso.estadoActual',
            'proceso.historialTransiciones.transicion',
            'proceso.historialTransiciones.user',
        ]);

        $html = View::make('adquisiciones.solicitud-compra-pdf', [
            'proceso' => $proceso,
            'logoBase64' => $this->logoBase64(),
            'aprobacion' => $this->resolverAprobacion($proceso),
        ])->render();

        return Pdf::loadHTML($html)->output();
    }

    private function resolverAprobacion(ProcesoAdquisicion $proceso): ?HistorialTransicionWorkflow
    {
        return $proceso->proceso?->historialTransiciones
            ->sortByDesc('id')
            ->first(fn (HistorialTransicionWorkflow $item) => $item->transicion->codigo === self::CODIGO_TRANSICION_APROBACION);
    }

    private function logoBase64(): string
    {
        $ruta = public_path('images/logo-capj-light.png');

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($ruta));
    }
}
