<?php

namespace App\Http\Controllers\Sgf;

use App\Http\Controllers\Controller;
use App\Models\TrabajoIntegracion;
use App\Services\Integraciones\EliminarImportacionSgfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use RuntimeException;

class EliminarImportacionSgfController extends Controller
{
    public function __construct(
        private readonly EliminarImportacionSgfService $eliminarImportacion,
    ) {}

    public function destroy(TrabajoIntegracion $trabajoIntegracion, Request $request): RedirectResponse
    {
        Gate::authorize('eliminar', $trabajoIntegracion);

        try {
            $this->eliminarImportacion->eliminar($trabajoIntegracion, $request->user());
        } catch (RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Importación eliminada.']);

        return to_route('sgf.importaciones.index');
    }
}
