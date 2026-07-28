<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\GuardarGraficoInformeRazonadoRequest;
use App\Models\EjecucionInformeRazonado;
use App\Models\GraficoInformeRazonado;
use App\Models\SeccionInformeRazonado;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class GraficoInformeRazonadoController extends Controller
{
    public function __construct(private readonly InformeRazonadoService $servicio) {}

    public function store(EjecucionInformeRazonado $ejecucion, GuardarGraficoInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('create', [GraficoInformeRazonado::class, $ejecucion]);

        $this->servicio->agregarGrafico(
            $ejecucion,
            (string) $request->validated('codigo'),
            (string) $request->validated('titulo'),
            (string) $request->validated('tipo'),
            (array) $request->validated('datos'),
            $this->seccionDe($request),
            $request->integer('orden'),
        );

        return back();
    }

    public function update(GraficoInformeRazonado $grafico, GuardarGraficoInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $grafico);

        $this->servicio->editarGrafico(
            $grafico,
            (string) $request->validated('titulo'),
            (string) $request->validated('tipo'),
            (array) $request->validated('datos'),
            $request->integer('orden'),
            $this->seccionDe($request),
        );

        return back();
    }

    public function destroy(GraficoInformeRazonado $grafico): RedirectResponse
    {
        Gate::authorize('delete', $grafico);

        $this->servicio->eliminarGrafico($grafico);

        return back();
    }

    private function seccionDe(GuardarGraficoInformeRazonadoRequest $request): ?SeccionInformeRazonado
    {
        return $request->filled('seccion_informe_razonado_id')
            ? SeccionInformeRazonado::find($request->integer('seccion_informe_razonado_id'))
            : null;
    }
}
