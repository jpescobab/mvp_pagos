<?php

namespace App\Services\Adquisiciones;

use App\Models\ProcesoAdquisicion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ExportadorSolicitudCompraPdfService
{
    public function generar(ProcesoAdquisicion $proceso): string
    {
        $proceso->loadMissing(['modalidad', 'ccosto', 'funcionarioRequirente', 'proceso.estadoActual']);

        $html = View::make('adquisiciones.solicitud-compra-pdf', [
            'proceso' => $proceso,
        ])->render();

        return Pdf::loadHTML($html)->output();
    }
}
