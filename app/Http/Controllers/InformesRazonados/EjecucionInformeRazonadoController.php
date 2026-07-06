<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Exceptions\CorteReportabilidadException;
use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\IniciarEjecucionInformeRazonadoRequest;
use App\Http\Resources\InformesRazonados\EjecucionInformeRazonadoResource;
use App\Models\CorteReportabilidad;
use App\Models\DefinicionInformeRazonado;
use App\Models\EjecucionInformeRazonado;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EjecucionInformeRazonadoController extends Controller
{
    public function __construct(private readonly InformeRazonadoService $servicio) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', EjecucionInformeRazonado::class);

        $ejecuciones = EjecucionInformeRazonado::with(['definicionInformeRazonado', 'corteReportabilidad.periodoReportabilidad', 'proceso.estadoActual'])
            ->orderByDesc('generado_en')
            ->get();

        return Inertia::render('informes-razonados/ejecuciones/index', [
            'ejecuciones' => EjecucionInformeRazonadoResource::collection($ejecuciones),
            'definiciones' => DefinicionInformeRazonado::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'cortesPublicados' => CorteReportabilidad::where('estado', 'publicado')
                ->with('periodoReportabilidad')
                ->orderByDesc('fecha_corte')
                ->get(['id', 'periodo_reportabilidad_id', 'fecha_corte']),
        ]);
    }

    public function store(IniciarEjecucionInformeRazonadoRequest $request): RedirectResponse
    {
        $definicion = DefinicionInformeRazonado::findOrFail($request->integer('definicion_informe_razonado_id'));
        $corte = CorteReportabilidad::findOrFail($request->integer('corte_reportabilidad_id'));

        try {
            $this->servicio->iniciarEjecucion($definicion, $corte);
        } catch (CorteReportabilidadException $e) {
            return back()->withErrors(['corte_reportabilidad_id' => $e->getMessage()]);
        }

        return back();
    }

    public function show(EjecucionInformeRazonado $ejecucion): Response
    {
        $ejecucion->load([
            'definicionInformeRazonado',
            'corteReportabilidad.periodoReportabilidad',
            'generadoPor',
            'proceso.estadoActual',
            'proceso.definicionWorkflow.transiciones',
            'proceso.historialTransiciones.transicion',
            'proceso.historialTransiciones.estadoOrigen',
            'proceso.historialTransiciones.estadoDestino',
            'proceso.historialTransiciones.user',
            'secciones',
            'metricas',
            'graficos',
            'narrativas',
            'excepciones',
            'snapshots',
            'aprobaciones.aprobadoPor',
            'exportaciones.generadoPor',
        ]);

        return Inertia::render('informes-razonados/ejecuciones/show', [
            'ejecucion' => new EjecucionInformeRazonadoResource($ejecucion),
        ]);
    }
}
