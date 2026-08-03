<?php

namespace App\Http\Controllers\Contratos;

use App\Exceptions\ContratoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contratos\GenerarCalendarioPagoRequest;
use App\Http\Requests\Contratos\UpdateContratoCuotaRequest;
use App\Http\Requests\Contratos\VincularPagoContratoCuotaRequest;
use App\Models\CasoPagoProveedor;
use App\Models\Contrato;
use App\Models\ContratoCuota;
use App\Services\Contratos\ContratoCalendarioPagoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ContratoCuotaController extends Controller
{
    public function generar(Contrato $contrato, GenerarCalendarioPagoRequest $request, ContratoCalendarioPagoService $servicio): RedirectResponse
    {
        Gate::authorize('update', $contrato);

        try {
            $servicio->generarCalendario($contrato);
        } catch (ContratoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back();
    }

    public function update(Contrato $contrato, ContratoCuota $cuota, UpdateContratoCuotaRequest $request, ContratoCalendarioPagoService $servicio): RedirectResponse
    {
        Gate::authorize('update', $contrato);

        try {
            $servicio->actualizarCuota($cuota, $request->validated());
        } catch (ContratoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back();
    }

    public function vincularPago(Contrato $contrato, ContratoCuota $cuota, VincularPagoContratoCuotaRequest $request, ContratoCalendarioPagoService $servicio): RedirectResponse
    {
        Gate::authorize('vincularPago', $contrato);

        $casoPagoProveedor = CasoPagoProveedor::findOrFail($request->integer('caso_pago_proveedor_id'));

        try {
            $servicio->vincularPago($cuota, $casoPagoProveedor);
        } catch (ContratoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back();
    }

    public function desvincularPago(Contrato $contrato, ContratoCuota $cuota, ContratoCalendarioPagoService $servicio): RedirectResponse
    {
        Gate::authorize('vincularPago', $contrato);

        $servicio->desvincularPago($cuota);

        return back();
    }
}
