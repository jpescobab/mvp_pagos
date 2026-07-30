<?php

namespace App\Http\Controllers\Presupuesto;

use App\Http\Controllers\Controller;
use App\Http\Requests\Presupuesto\ImportarPresupuestoRequest;
use App\Http\Resources\Presupuesto\ImportacionPresupuestoResource;
use App\Models\Presupuesto\ImportacionPresupuesto;
use App\Services\Presupuesto\ImportadorPresupuestoCguService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class ImportacionPresupuestoController extends Controller
{
    public function __construct(
        private readonly ImportadorPresupuestoCguService $importador,
    ) {}

    public function index(): Response
    {
        Gate::authorize('viewAny', ImportacionPresupuesto::class);

        $importaciones = ImportacionPresupuesto::query()
            ->with('creadoPor')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('presupuesto/importaciones/index', [
            'importaciones' => ImportacionPresupuestoResource::collection($importaciones),
        ]);
    }

    public function store(ImportarPresupuestoRequest $request): RedirectResponse
    {
        $archivo = $request->file('archivo');

        try {
            $importacion = $this->importador->importar(
                $archivo->getRealPath(),
                $request->integer('anio'),
                $request->user(),
                $archivo,
            );
        } catch (RuntimeException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se pudo importar el Excel: {$e->getMessage()}"]);

            return back();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Importación versión \"{$importacion->nro_version}\" completada: {$importacion->total_creados} creadas, {$importacion->total_actualizados} actualizadas, {$importacion->total_omitidos} omitidas."]);

        return to_route('presupuesto.importaciones.index');
    }
}
