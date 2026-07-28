<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\GuardarMetricaInformeRazonadoRequest;
use App\Models\EjecucionInformeRazonado;
use App\Models\MetricaInformeRazonado;
use App\Models\SeccionInformeRazonado;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MetricaInformeRazonadoController extends Controller
{
    public function __construct(private readonly InformeRazonadoService $servicio) {}

    public function store(EjecucionInformeRazonado $ejecucion, GuardarMetricaInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('create', [MetricaInformeRazonado::class, $ejecucion]);

        $this->servicio->agregarMetrica(
            $ejecucion,
            (string) $request->validated('codigo'),
            (string) $request->validated('etiqueta'),
            $request->filled('valor') ? (float) $request->validated('valor') : null,
            $request->validated('unidad'),
            $this->seccionDe($request),
            $request->integer('orden'),
        );

        return back();
    }

    public function update(MetricaInformeRazonado $metrica, GuardarMetricaInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $metrica);

        $this->servicio->editarMetrica(
            $metrica,
            (string) $request->validated('etiqueta'),
            $request->filled('valor') ? (float) $request->validated('valor') : null,
            $request->validated('unidad'),
            $request->integer('orden'),
            $this->seccionDe($request),
        );

        return back();
    }

    public function destroy(MetricaInformeRazonado $metrica): RedirectResponse
    {
        Gate::authorize('delete', $metrica);

        $this->servicio->eliminarMetrica($metrica);

        return back();
    }

    private function seccionDe(GuardarMetricaInformeRazonadoRequest $request): ?SeccionInformeRazonado
    {
        return $request->filled('seccion_informe_razonado_id')
            ? SeccionInformeRazonado::find($request->integer('seccion_informe_razonado_id'))
            : null;
    }
}
