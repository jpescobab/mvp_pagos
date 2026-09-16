<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreTipoCompraRequest;
use App\Http\Requests\Maestros\UpdateTipoCompraRequest;
use App\Http\Resources\Maestros\TipoCompraResource;
use App\Models\TipoCompra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TipoCompraController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', TipoCompra::class);

        $tiposCompra = TipoCompra::query()->orderBy('nombre')->get();

        return Inertia::render('maestros/tipos-compra/index', [
            'tiposCompra' => TipoCompraResource::collection($tiposCompra),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', TipoCompra::class);

        return Inertia::render('maestros/tipos-compra/create');
    }

    public function store(StoreTipoCompraRequest $request): RedirectResponse
    {
        Gate::authorize('create', TipoCompra::class);

        $tipoCompra = TipoCompra::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de compra \"{$tipoCompra->nombre}\" registrado."]);

        return to_route('maestros.tipos-compra.index');
    }

    public function show(TipoCompra $tipoCompra): Response
    {
        Gate::authorize('view', $tipoCompra);

        return Inertia::render('maestros/tipos-compra/show', [
            'tipoCompra' => new TipoCompraResource($tipoCompra),
        ]);
    }

    public function edit(TipoCompra $tipoCompra): Response
    {
        Gate::authorize('update', $tipoCompra);

        return Inertia::render('maestros/tipos-compra/edit', [
            'tipoCompra' => new TipoCompraResource($tipoCompra),
        ]);
    }

    public function update(UpdateTipoCompraRequest $request, TipoCompra $tipoCompra): RedirectResponse
    {
        Gate::authorize('update', $tipoCompra);

        $tipoCompra->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de compra \"{$tipoCompra->nombre}\" actualizado."]);

        return to_route('maestros.tipos-compra.show', $tipoCompra);
    }

    public function destroy(TipoCompra $tipoCompra): RedirectResponse
    {
        Gate::authorize('delete', $tipoCompra);

        if ($tipoCompra->detallesFactura()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar: tiene detalles de factura asociados.']);

            return back();
        }

        $tipoCompra->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de compra \"{$tipoCompra->nombre}\" eliminado."]);

        return to_route('maestros.tipos-compra.index');
    }
}
