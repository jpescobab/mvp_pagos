<?php

namespace App\Http\Controllers\Reportabilidad;

use App\Exceptions\CorteReportabilidadException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Reportabilidad\CorteReportabilidadResource;
use App\Models\CorteReportabilidad;
use App\Models\PeriodoReportabilidad;
use App\Services\Reportabilidad\CorteReportabilidadService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CorteReportabilidadController extends Controller
{
    public function __construct(private readonly CorteReportabilidadService $servicio) {}

    public function show(CorteReportabilidad $corte): Response
    {
        $corte->loadCount(['items', 'snapshots', 'ejecucionesInformeRazonado']);
        $corte->load('periodoReportabilidad', 'publicadoPor');

        return Inertia::render('reportabilidad/cortes/show', [
            'corte' => new CorteReportabilidadResource($corte),
        ]);
    }

    public function store(PeriodoReportabilidad $periodo): RedirectResponse
    {
        $this->servicio->crearCorte($periodo);

        return back();
    }

    public function publicar(CorteReportabilidad $corte): RedirectResponse
    {
        try {
            $this->servicio->publicarCorte($corte);
        } catch (CorteReportabilidadException $e) {
            return back()->withErrors(['corte' => $e->getMessage()]);
        }

        return back();
    }
}
