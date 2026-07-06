<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreCatalogoRequest;
use App\Http\Requests\Maestros\UpdateCatalogoRequest;
use App\Models\Catalogo;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CatalogoController extends Controller
{
    public function store(Item $item, StoreCatalogoRequest $request): RedirectResponse
    {
        Gate::authorize('create', Catalogo::class);

        $catalogo = $item->catalogos()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Catálogo \"{$catalogo->nombre}\" registrado."]);

        return back();
    }

    public function update(Item $item, Catalogo $catalogo, UpdateCatalogoRequest $request): RedirectResponse
    {
        Gate::authorize('update', $catalogo);

        $catalogo->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Catálogo \"{$catalogo->nombre}\" actualizado."]);

        return back();
    }

    public function destroy(Item $item, Catalogo $catalogo): RedirectResponse
    {
        Gate::authorize('delete', $catalogo);

        $catalogo->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Catálogo \"{$catalogo->nombre}\" eliminado."]);

        return back();
    }
}
