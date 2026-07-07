<?php

namespace App\Http\Controllers\Sgf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sgf\ImportacionSgfResource;
use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use Inertia\Inertia;
use Inertia\Response;

class ImportacionSgfController extends Controller
{
    public function index(): Response
    {
        $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();

        $importaciones = TrabajoIntegracion::where('sistema_externo_id', $sistema->id)
            ->with('iniciadoPor')
            ->latest('iniciado_en')
            ->paginate(20);

        return Inertia::render('sgf/importaciones/index', [
            'importaciones' => ImportacionSgfResource::collection($importaciones),
        ]);
    }

    public function show(TrabajoIntegracion $trabajoIntegracion): Response
    {
        $trabajoIntegracion->load(['iniciadoPor', 'snapshotsDatosExternos']);

        return Inertia::render('sgf/importaciones/show', [
            'importacion' => new ImportacionSgfResource($trabajoIntegracion),
        ]);
    }
}
