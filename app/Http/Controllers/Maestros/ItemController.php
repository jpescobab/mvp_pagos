<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreItemRequest;
use App\Http\Requests\Maestros\UpdateItemRequest;
use App\Http\Resources\Maestros\ItemResource;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Item::class);

        $q = $request->string('q')->toString();

        $items = Item::query()
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/items/index', [
            'items' => ItemResource::collection($items),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Item::class);

        return Inertia::render('maestros/items/create');
    }

    public function store(StoreItemRequest $request): RedirectResponse
    {
        Gate::authorize('create', Item::class);

        $item = Item::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Ítem \"{$item->nombre}\" registrado."]);

        return to_route('maestros.items.index');
    }

    public function show(Item $item): Response
    {
        Gate::authorize('view', $item);

        $item->load(['asignaciones', 'catalogos']);

        return Inertia::render('maestros/items/show', [
            'item' => new ItemResource($item),
        ]);
    }

    public function edit(Item $item): Response
    {
        Gate::authorize('update', $item);

        return Inertia::render('maestros/items/edit', [
            'item' => new ItemResource($item),
        ]);
    }

    public function update(UpdateItemRequest $request, Item $item): RedirectResponse
    {
        Gate::authorize('update', $item);

        $item->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Ítem \"{$item->nombre}\" actualizado."]);

        return to_route('maestros.items.show', $item);
    }

    public function destroy(Item $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $bloqueo = $this->relacionQueImpideEliminar($item);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociados."]);

            return back();
        }

        $item->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Ítem \"{$item->nombre}\" eliminado."]);

        return to_route('maestros.items.index');
    }

    private function relacionQueImpideEliminar(Item $item): ?string
    {
        if ($item->asignaciones()->exists()) {
            return 'asignaciones';
        }

        if ($item->catalogos()->exists()) {
            return 'catálogos';
        }

        return null;
    }
}
