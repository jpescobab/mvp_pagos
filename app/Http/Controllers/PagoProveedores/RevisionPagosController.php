<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Models\EgresoCgu;
use App\Services\PagoProveedores\RevisionEgresoPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RevisionPagosController extends Controller
{
    public function __construct(private readonly RevisionEgresoPresenter $presenter) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('pago-proveedores/revision/index', [
            'egresos' => $this->presenter->listadoEnRevision($user),
            'permisos' => [
                'revisar_finanzas' => $user->can('pago_proveedores.revisar_finanzas'),
                'revisar_zonal' => $user->can('pago_proveedores.revisar_zonal'),
            ],
        ]);
    }

    /**
     * Deep-link a un egreso concreto: se autoriza y se abre el workbench con
     * ese egreso preseleccionado.
     */
    public function show(EgresoCgu $egresoCgu, Request $request): Response|RedirectResponse
    {
        Gate::authorize('revisar', $egresoCgu);

        $user = $request->user();

        return Inertia::render('pago-proveedores/revision/index', [
            'egresos' => $this->presenter->listadoEnRevision($user),
            'egresoInicial' => $egresoCgu->id,
            'permisos' => [
                'revisar_finanzas' => $user->can('pago_proveedores.revisar_finanzas'),
                'revisar_zonal' => $user->can('pago_proveedores.revisar_zonal'),
            ],
        ]);
    }
}
