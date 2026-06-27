<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\EjecutarTransicionRequest;
use App\Models\CasoPagoProveedor;
use App\Services\Workflow\TransicionWorkflowService;
use Illuminate\Http\RedirectResponse;

class TransicionCasoPagoProveedorController extends Controller
{
    public function store(CasoPagoProveedor $caso, EjecutarTransicionRequest $request, TransicionWorkflowService $servicio): RedirectResponse
    {
        try {
            $servicio->execute(
                $caso->proceso,
                $request->string('codigo')->toString(),
                $request->string('comentario')->toString() ?: null,
            );
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back();
    }
}
