<?php

namespace App\Http\Controllers\Sgf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sgf\ImportacionSgfResource;
use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ImportacionSgfController extends Controller
{
    public function index(Request $request): Response
    {
        $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
        $q = $request->string('q')->trim()->toString();

        $importaciones = TrabajoIntegracion::where('sistema_externo_id', $sistema->id)
            ->with('iniciadoPor')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('tipo', 'like', "%{$q}%")
                    ->orWhereHas('iniciadoPor', fn ($usuario) => $usuario->where('name', 'like', "%{$q}%"))
            ))
            ->latest('iniciado_en')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('sgf/importaciones/index', [
            'importaciones' => ImportacionSgfResource::collection($importaciones),
            'q' => $q !== '' ? $q : null,
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
