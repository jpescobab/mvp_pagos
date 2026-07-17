<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\ActualizarRequisitoDocumentalRequest;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Services\PagoProveedores\RequisitoDocumentalPagoProveedorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequisitoDocumentalController extends Controller
{
    public function __construct(private readonly RequisitoDocumentalPagoProveedorService $requisitos) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('pago_proveedores.administrar_requisitos_documentales'), 403);

        return Inertia::render('pago-proveedores/requisitos-documentales/index', [
            'tiposDocumento' => TipoDocumento::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'tiposProcesoPago' => TipoProcesoPago::where('activo', true)->orderBy('nombre')->get(['id', 'codigo', 'nombre']),
            'requisitos' => $this->requisitos->vigentes(),
        ]);
    }

    public function update(TipoDocumento $tipoDocumento, ActualizarRequisitoDocumentalRequest $request): RedirectResponse
    {
        $this->requisitos->actualizar(
            $tipoDocumento,
            $request->validated('tipo_proceso_pago_id'),
            $request->validated('tipo_requisito'),
        );

        return back();
    }
}
