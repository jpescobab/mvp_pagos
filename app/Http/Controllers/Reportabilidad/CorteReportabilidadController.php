<?php

namespace App\Http\Controllers\Reportabilidad;

use App\Exceptions\CorteReportabilidadException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Reportabilidad\CorteReportabilidadResource;
use App\Models\CorteReportabilidad;
use App\Models\PeriodoReportabilidad;
use App\Services\Reportabilidad\CorteReportabilidadService;
use App\Services\Reportabilidad\GeneradorCorteReportabilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CorteReportabilidadController extends Controller
{
    public function __construct(
        private readonly CorteReportabilidadService $servicio,
        private readonly GeneradorCorteReportabilidadService $generador,
    ) {}

    public function show(CorteReportabilidad $corte): Response
    {
        $corte->loadCount(['items', 'snapshots', 'ejecucionesInformeRazonado']);
        $corte->load('periodoReportabilidad', 'publicadoPor', 'items');

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

    public function generar(CorteReportabilidad $corte): RedirectResponse
    {
        Gate::authorize('generar', $corte);

        try {
            $this->generador->generar($corte);
        } catch (CorteReportabilidadException $e) {
            return back()->withErrors(['corte' => $e->getMessage()]);
        }

        return back();
    }
}
