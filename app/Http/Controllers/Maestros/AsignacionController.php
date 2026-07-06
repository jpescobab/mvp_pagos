<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreAsignacionRequest;
use App\Http\Requests\Maestros\UpdateAsignacionRequest;
use App\Models\Asignacion;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AsignacionController extends Controller
{
    public function store(Item $item, StoreAsignacionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Asignacion::class);

        $asignacion = $item->asignaciones()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Asignación \"{$asignacion->nombre}\" registrada."]);

        return back();
    }

    public function update(Item $item, Asignacion $asignacion, UpdateAsignacionRequest $request): RedirectResponse
    {
        Gate::authorize('update', $asignacion);

        $asignacion->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Asignación \"{$asignacion->nombre}\" actualizada."]);

        return back();
    }

    public function destroy(Item $item, Asignacion $asignacion): RedirectResponse
    {
        Gate::authorize('delete', $asignacion);

        $asignacion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Asignación \"{$asignacion->nombre}\" eliminada."]);

        return back();
    }
}
