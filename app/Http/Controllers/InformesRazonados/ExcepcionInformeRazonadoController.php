<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\GuardarExcepcionInformeRazonadoRequest;
use App\Models\EjecucionInformeRazonado;
use App\Models\ExcepcionInformeRazonado;
use App\Services\InformesRazonados\InformeRazonadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ExcepcionInformeRazonadoController extends Controller
{
    public function __construct(private readonly InformeRazonadoService $servicio) {}

    public function store(EjecucionInformeRazonado $ejecucion, GuardarExcepcionInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('create', [ExcepcionInformeRazonado::class, $ejecucion]);

        $this->servicio->agregarExcepcion(
            $ejecucion,
            (string) $request->validated('codigo'),
            (string) $request->validated('descripcion'),
            (string) $request->validated('severidad'),
        );

        return back();
    }

    public function update(ExcepcionInformeRazonado $excepcion, GuardarExcepcionInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $excepcion);

        $this->servicio->editarExcepcion(
            $excepcion,
            (string) $request->validated('descripcion'),
            (string) $request->validated('severidad'),
        );

        return back();
    }

    public function destroy(ExcepcionInformeRazonado $excepcion): RedirectResponse
    {
        Gate::authorize('delete', $excepcion);

        $this->servicio->eliminarExcepcion($excepcion);

        return back();
    }
}
