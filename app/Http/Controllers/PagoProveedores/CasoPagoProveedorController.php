<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Resources\PagoProveedores\CasoPagoProveedorResource;
use App\Models\CasoPagoProveedor;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CasoPagoProveedorController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', CasoPagoProveedor::class);

        $casos = CasoPagoProveedor::with(['proveedor', 'proceso.estadoActual', 'proceso.definicionWorkflow.transiciones'])
            ->paginate(20);

        return Inertia::render('pago-proveedores/casos/index', [
            'casos' => CasoPagoProveedorResource::collection($casos),
        ]);
    }

    public function show(CasoPagoProveedor $caso): Response
    {
        Gate::authorize('view', $caso);

        $caso->load([
            'proveedor',
            'proceso.estadoActual',
            'proceso.definicionWorkflow.transiciones',
            'proceso.historialTransiciones.transicion',
            'proceso.historialTransiciones.estadoOrigen',
            'proceso.historialTransiciones.estadoDestino',
            'proceso.historialTransiciones.user',
            'proceso.checklist.items',
        ]);

        return Inertia::render('pago-proveedores/casos/show', [
            'caso' => new CasoPagoProveedorResource($caso),
        ]);
    }
}
