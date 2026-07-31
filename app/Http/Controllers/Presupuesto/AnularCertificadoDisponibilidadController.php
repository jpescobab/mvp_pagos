<?php

namespace App\Http\Controllers\Presupuesto;

use App\Exceptions\CertificadoDisponibilidadPresupuestariaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presupuesto\AnularCertificadoDisponibilidadRequest;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Services\Presupuesto\AnularCertificadoDisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AnularCertificadoDisponibilidadController extends Controller
{
    public function store(CertificadoDisponibilidadPresupuestaria $cdp, AnularCertificadoDisponibilidadRequest $request, AnularCertificadoDisponibilidadService $servicio): RedirectResponse
    {
        Gate::authorize('presupuesto.anular_cdp');

        try {
            $anulacion = $servicio->anular($cdp, $request->string('comentario')->toString() ?: null);
        } catch (CertificadoDisponibilidadPresupuestariaException $e) {
            return back()->withErrors(['estado' => $e->getMessage()]);
        }

        return to_route('presupuesto.cdps.show', $anulacion);
    }
}
