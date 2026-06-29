<?php

namespace App\Http\Controllers\Reportabilidad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reportabilidad\AbrirPeriodoReportabilidadRequest;
use App\Http\Resources\Reportabilidad\PeriodoReportabilidadResource;
use App\Models\PeriodoReportabilidad;
use App\Services\Reportabilidad\CorteReportabilidadService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PeriodoReportabilidadController extends Controller
{
    public function __construct(private readonly CorteReportabilidadService $servicio) {}

    public function index(): Response
    {
        $periodos = PeriodoReportabilidad::withCount('cortesReportabilidad')
            ->with('cortesReportabilidad')
            ->orderByDesc('fecha_inicio')
            ->get();

        return Inertia::render('reportabilidad/periodos/index', [
            'periodos' => PeriodoReportabilidadResource::collection($periodos),
        ]);
    }

    public function store(AbrirPeriodoReportabilidadRequest $request): RedirectResponse
    {
        $this->servicio->abrirPeriodo(
            $request->string('codigo')->toString(),
            $request->string('fecha_inicio')->toString(),
            $request->string('fecha_fin')->toString(),
        );

        return back();
    }
}
