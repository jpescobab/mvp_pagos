<?php

namespace App\Http\Controllers\Integraciones;

use App\Http\Controllers\Controller;
use App\Http\Resources\Integraciones\SistemaExternoResource;
use App\Models\SistemaExterno;
use Inertia\Inertia;
use Inertia\Response;

class SistemaExternoController extends Controller
{
    public function index(): Response
    {
        $sistemas = SistemaExterno::withCount('trabajosIntegracion')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('integraciones/sistemas-externos/index', [
            'sistemas' => SistemaExternoResource::collection($sistemas),
        ]);
    }
}
