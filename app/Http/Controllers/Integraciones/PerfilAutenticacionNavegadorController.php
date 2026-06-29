<?php

namespace App\Http\Controllers\Integraciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integraciones\CrearPerfilAutenticacionNavegadorRequest;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\PerfilAutenticacionNavegador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class PerfilAutenticacionNavegadorController extends Controller
{
    public function store(ConectorAutomatizacionNavegador $conector, CrearPerfilAutenticacionNavegadorRequest $request): RedirectResponse
    {
        Gate::authorize('gestionar', $conector);

        PerfilAutenticacionNavegador::create([
            ...$request->validated(),
            'conector_automatizacion_navegador_id' => $conector->id,
            'creado_por' => $request->user()->id,
        ]);

        return back();
    }
}
