<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\GuardarNarrativaInformeRazonadoRequest;
use App\Http\Requests\InformesRazonados\RevisarNarrativaInformeRazonadoRequest;
use App\Models\EjecucionInformeRazonado;
use App\Models\NarrativaInformeRazonado;
use App\Models\SeccionInformeRazonado;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class NarrativaInformeRazonadoController extends Controller
{
    public function __construct(private readonly InformeRazonadoService $servicio) {}

    public function store(EjecucionInformeRazonado $ejecucion, GuardarNarrativaInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('create', [NarrativaInformeRazonado::class, $ejecucion]);

        $seccion = $request->filled('seccion_informe_razonado_id')
            ? SeccionInformeRazonado::find($request->integer('seccion_informe_razonado_id'))
            : null;

        $this->servicio->agregarNarrativa(
            $ejecucion,
            (string) $request->validated('contenido'),
            $request->boolean('generado_por_ia'),
            $seccion,
        );

        return back();
    }

    public function update(NarrativaInformeRazonado $narrativa, GuardarNarrativaInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $narrativa);

        $this->servicio->editarNarrativa($narrativa, (string) $request->validated('contenido'));

        return back();
    }

    public function destroy(NarrativaInformeRazonado $narrativa): RedirectResponse
    {
        Gate::authorize('delete', $narrativa);

        $this->servicio->eliminarNarrativa($narrativa);

        return back();
    }

    public function revisar(NarrativaInformeRazonado $narrativa, RevisarNarrativaInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('revisar', $narrativa);

        $this->servicio->revisarNarrativa($narrativa);

        return back();
    }
}
