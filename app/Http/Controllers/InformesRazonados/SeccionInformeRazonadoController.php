<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\GuardarSeccionInformeRazonadoRequest;
use App\Models\EjecucionInformeRazonado;
use App\Models\SeccionInformeRazonado;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SeccionInformeRazonadoController extends Controller
{
    public function __construct(private readonly InformeRazonadoService $servicio) {}

    public function store(EjecucionInformeRazonado $ejecucion, GuardarSeccionInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('create', [SeccionInformeRazonado::class, $ejecucion]);

        $this->servicio->agregarSeccion(
            $ejecucion,
            (string) $request->validated('codigo'),
            (string) $request->validated('titulo'),
            $request->integer('orden'),
        );

        return back();
    }

    public function update(SeccionInformeRazonado $seccion, GuardarSeccionInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $seccion);

        $this->servicio->editarSeccion(
            $seccion,
            (string) $request->validated('titulo'),
            $request->integer('orden'),
        );

        return back();
    }

    public function destroy(SeccionInformeRazonado $seccion): RedirectResponse
    {
        Gate::authorize('delete', $seccion);

        $this->servicio->eliminarSeccion($seccion);

        return back();
    }
}
