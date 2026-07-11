<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Exceptions\TransicionWorkflowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\TransicionEgresoRevisionRequest;
use App\Models\EgresoCgu;
use App\Services\PagoProveedores\RevisionEgresoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class RevisionTransicionEgresoController extends Controller
{
    public function __construct(private readonly RevisionEgresoService $revision) {}

    public function store(EgresoCgu $egresoCgu, TransicionEgresoRevisionRequest $request): RedirectResponse
    {
        Gate::authorize('revisar', $egresoCgu);

        $accion = $request->string('accion')->toString();
        $comentario = $request->string('comentario')->toString();
        $user = $request->user();

        try {
            match ($accion) {
                'aprobar' => $this->revision->aprobarEgreso($egresoCgu, $user),
                'devolver' => $this->revision->devolverEgreso($egresoCgu, $comentario, $user),
                default => throw new RuntimeException("Acción no soportada: {$accion}"),
            };
        } catch (TransicionWorkflowException|RuntimeException $e) {
            return back()->with('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Egreso actualizado.']);
    }
}
