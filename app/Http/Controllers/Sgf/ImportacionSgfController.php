<?php

namespace App\Http\Controllers\Sgf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Sgf\ImportacionSgfResource;
use App\Models\ImportacionSgf;
use Inertia\Inertia;
use Inertia\Response;

class ImportacionSgfController extends Controller
{
    public function index(): Response
    {
        $importaciones = ImportacionSgf::with('iniciadoPor')
            ->latest('iniciado_en')
            ->paginate(20);

        return Inertia::render('sgf/importaciones/index', [
            'importaciones' => ImportacionSgfResource::collection($importaciones),
        ]);
    }

    public function show(ImportacionSgf $importacionSgf): Response
    {
        $importacionSgf->load(['iniciadoPor', 'snapshots']);

        return Inertia::render('sgf/importaciones/show', [
            'importacion' => new ImportacionSgfResource($importacionSgf),
        ]);
    }
}
