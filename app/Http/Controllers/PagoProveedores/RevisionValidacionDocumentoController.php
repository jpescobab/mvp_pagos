<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\ValidarDocumentoRevisionRequest;
use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\EgresoCgu;
use App\Services\PagoProveedores\RevisionEgresoService;
use App\Services\PagoProveedores\ValidacionDocumentoInstanciaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RevisionValidacionDocumentoController extends Controller
{
    public function __construct(
        private readonly RevisionEgresoService $revision,
        private readonly ValidacionDocumentoInstanciaService $validaciones,
    ) {}

    public function store(
        EgresoCgu $egresoCgu,
        CasoPagoProveedor $caso,
        Documento $documento,
        ValidarDocumentoRevisionRequest $request,
    ): RedirectResponse {
        Gate::authorize('revisar', $egresoCgu);

        $instancia = $this->revision->instanciaDelCaso($caso);

        if ($instancia === null) {
            return back()->with('toast', ['type' => 'error', 'message' => 'El pago no está en una instancia de revisión.']);
        }

        Gate::authorize(
            $instancia === InstanciaRevision::Finanzas ? 'revisarFinanzas' : 'revisarZonal',
            $egresoCgu,
        );

        $this->validaciones->validar(
            $documento,
            $instancia,
            $request->string('estado')->toString(),
            $request->string('observacion')->toString() ?: null,
            $request->user(),
        );

        return back();
    }
}
