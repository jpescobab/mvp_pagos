<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\CrearDefinicionInformeRazonadoRequest;
use App\Http\Resources\InformesRazonados\DefinicionInformeRazonadoResource;
use App\Models\DefinicionInformeRazonado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DefinicionInformeRazonadoController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', DefinicionInformeRazonado::class);

        $definiciones = DefinicionInformeRazonado::withCount('ejecuciones')
            ->orderBy('codigo')
            ->get();

        return Inertia::render('informes-razonados/definiciones/index', [
            'definiciones' => DefinicionInformeRazonadoResource::collection($definiciones),
        ]);
    }

    public function store(CrearDefinicionInformeRazonadoRequest $request): RedirectResponse
    {
        DefinicionInformeRazonado::create($request->validated());

        return back();
    }
}
