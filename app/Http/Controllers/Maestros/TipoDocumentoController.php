<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreTipoDocumentoRequest;
use App\Http\Requests\Maestros\UpdateTipoDocumentoRequest;
use App\Http\Resources\Maestros\TipoDocumentoResource;
use App\Models\TipoDocumento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TipoDocumentoController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', TipoDocumento::class);

        $tiposDocumento = TipoDocumento::query()->orderBy('nombre')->get();

        return Inertia::render('maestros/tipos-documento/index', [
            'tiposDocumento' => TipoDocumentoResource::collection($tiposDocumento),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', TipoDocumento::class);

        return Inertia::render('maestros/tipos-documento/create');
    }

    public function store(StoreTipoDocumentoRequest $request): RedirectResponse
    {
        Gate::authorize('create', TipoDocumento::class);

        $tipoDocumento = TipoDocumento::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de documento \"{$tipoDocumento->nombre}\" registrado."]);

        return to_route('maestros.tipos-documento.index');
    }

    public function show(TipoDocumento $tipoDocumento): Response
    {
        Gate::authorize('view', $tipoDocumento);

        return Inertia::render('maestros/tipos-documento/show', [
            'tipoDocumento' => new TipoDocumentoResource($tipoDocumento),
        ]);
    }

    public function edit(TipoDocumento $tipoDocumento): Response
    {
        Gate::authorize('update', $tipoDocumento);

        return Inertia::render('maestros/tipos-documento/edit', [
            'tipoDocumento' => new TipoDocumentoResource($tipoDocumento),
        ]);
    }

    public function update(UpdateTipoDocumentoRequest $request, TipoDocumento $tipoDocumento): RedirectResponse
    {
        Gate::authorize('update', $tipoDocumento);

        $tipoDocumento->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de documento \"{$tipoDocumento->nombre}\" actualizado."]);

        return to_route('maestros.tipos-documento.show', $tipoDocumento);
    }

    public function destroy(TipoDocumento $tipoDocumento): RedirectResponse
    {
        Gate::authorize('delete', $tipoDocumento);

        $bloqueo = $this->relacionQueImpideEliminar($tipoDocumento);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociados."]);

            return back();
        }

        $tipoDocumento->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de documento \"{$tipoDocumento->nombre}\" eliminado."]);

        return to_route('maestros.tipos-documento.index');
    }

    private function relacionQueImpideEliminar(TipoDocumento $tipoDocumento): ?string
    {
        if ($tipoDocumento->documentos()->exists()) {
            return 'documentos';
        }

        if ($tipoDocumento->requisitos()->exists()) {
            return 'requisitos documentales';
        }

        return null;
    }
}
