<?php

namespace App\Http\Controllers\Contratos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contratos\VincularProcesoAdquisicionContratoRequest;
use App\Models\Contrato;
use App\Services\Contratos\ContratoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class VinculoContratoProcesoAdquisicionController extends Controller
{
    public function store(Contrato $contrato, VincularProcesoAdquisicionContratoRequest $request, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('vincular', $contrato);

        $servicio->vincularProcesoAdquisicion($contrato, $request->integer('proceso_adquisicion_id'));

        return back();
    }

    public function destroy(Contrato $contrato, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('vincular', $contrato);

        $servicio->desvincularProcesoAdquisicion($contrato);

        return back();
    }
}
