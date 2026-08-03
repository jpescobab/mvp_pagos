<?php

namespace App\Http\Controllers\Contratos;

use App\Exceptions\ContratoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contratos\StoreContratoItemConvenioPrecioRequest;
use App\Models\Contrato;
use App\Models\ContratoItemConvenioPrecio;
use App\Services\Contratos\ContratoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ContratoItemConvenioPrecioController extends Controller
{
    public function store(Contrato $contrato, StoreContratoItemConvenioPrecioRequest $request, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('update', $contrato);

        try {
            $servicio->agregarItemConvenioPrecio($contrato, $request->validated());
        } catch (ContratoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back();
    }

    public function destroy(Contrato $contrato, ContratoItemConvenioPrecio $item, ContratoService $servicio): RedirectResponse
    {
        Gate::authorize('update', $contrato);

        try {
            $servicio->eliminarItemConvenioPrecio($contrato, $item);
        } catch (ContratoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back();
    }
}
