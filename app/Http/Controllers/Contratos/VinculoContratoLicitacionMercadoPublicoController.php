<?php

namespace App\Http\Controllers\Contratos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contratos\VincularLicitacionMercadoPublicoContratoRequest;
use App\Models\Contrato;
use App\Services\Contratos\ContratoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class VinculoContratoLicitacionMercadoPublicoController extends Controller
{
    public function store(Contrato $contrato, VincularLicitacionMercadoPublicoContratoRequest $request, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('vincular', $contrato);

        $servicio->vincularLicitacionMercadoPublico($contrato, $request->integer('licitacion_mercado_publico_id'));

        return back();
    }

    public function destroy(Contrato $contrato, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('vincular', $contrato);

        $servicio->desvincularLicitacionMercadoPublico($contrato);

        return back();
    }
}
