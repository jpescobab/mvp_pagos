<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Http\Controllers\Controller;
use App\Models\CasoPagoProveedor;
use App\Models\EgresoCgu;
use App\Services\PagoProveedores\RevisionEgresoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RevisionTotalesController extends Controller
{
    public function __construct(private readonly RevisionEgresoService $revision) {}

    public function store(EgresoCgu $egresoCgu, CasoPagoProveedor $caso, Request $request): RedirectResponse
    {
        Gate::authorize('revisar', $egresoCgu);

        $instancia = $this->revision->instanciaDelCaso($caso);

        if ($instancia === null) {
            return back()->with('toast', ['type' => 'error', 'message' => 'El pago no está en una instancia de revisión.']);
        }

        Gate::authorize(
            $instancia === InstanciaRevision::Finanzas ? 'revisarFinanzas' : 'revisarZonal',
            $egresoCgu,
        );

        $this->revision->verificarTotales(
            $caso,
            $instancia,
            $request->user(),
            $request->boolean('verificado', true),
        );

        return back();
    }
}
