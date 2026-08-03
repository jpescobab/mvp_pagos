<?php

namespace App\Http\Controllers\Contratos;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\EjecutarTransicionRequest;
use App\Models\Contrato;
use App\Services\Workflow\TransicionWorkflowService;
use Illuminate\Http\RedirectResponse;

class TransicionContratoController extends Controller
{
    public function store(Contrato $contrato, EjecutarTransicionRequest $request, TransicionWorkflowService $servicio): RedirectResponse
    {
        try {
            $servicio->execute(
                $contrato->proceso,
                $request->string('codigo')->toString(),
                $request->string('comentario')->toString() ?: null,
            );
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back();
    }
}
