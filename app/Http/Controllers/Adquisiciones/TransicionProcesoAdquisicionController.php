<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\EjecutarTransicionRequest;
use App\Models\ProcesoAdquisicion;
use App\Services\Workflow\TransicionWorkflowService;
use Illuminate\Http\RedirectResponse;

class TransicionProcesoAdquisicionController extends Controller
{
    public function store(ProcesoAdquisicion $proceso, EjecutarTransicionRequest $request, TransicionWorkflowService $servicio): RedirectResponse
    {
        try {
            $servicio->execute(
                $proceso->proceso,
                $request->string('codigo')->toString(),
                $request->string('comentario')->toString() ?: null,
            );
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back();
    }
}
