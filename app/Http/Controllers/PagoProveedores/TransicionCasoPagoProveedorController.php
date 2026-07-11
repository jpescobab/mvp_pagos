<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
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
        $codigo = $request->string('codigo')->toString();

        if (in_array($codigo, InstanciaRevision::codigosTransicionGobernados(), true)) {
            return back()->withErrors([
                'transicion' => 'Esta transición se gobierna desde Revisión de Pagos, no desde esta pantalla.',
            ]);
        }

        try {
            $servicio->execute(
                $caso->proceso,
                $codigo,
                $request->string('comentario')->toString() ?: null,
            );
        } catch (TransicionWorkflowException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back();
    }
}
