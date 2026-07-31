<?php

namespace App\Http\Controllers\Presupuesto;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presupuesto\EjecutarTransicionCdpRequest;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Services\Presupuesto\FirmarCertificadoDisponibilidadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TransicionCertificadoDisponibilidadController extends Controller
{
    public function store(CertificadoDisponibilidadPresupuestaria $cdp, EjecutarTransicionCdpRequest $request, FirmarCertificadoDisponibilidadService $servicio): RedirectResponse
    {
        $codigo = $request->string('codigo')->toString();

        if ($codigo !== 'firmar') {
            throw ValidationException::withMessages(['codigo' => "Transición no soportada: {$codigo}."]);
        }

        try {
            $servicio->firmar($cdp, $request->string('comentario')->toString() ?: null);
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back();
    }
}
