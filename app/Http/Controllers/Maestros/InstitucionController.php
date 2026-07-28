<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreInstitucionRequest;
use App\Http\Requests\Maestros\UpdateInstitucionRequest;
use App\Http\Resources\Maestros\InstitucionResource;
use App\Models\Institucion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InstitucionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Institucion::class);

        $q = $request->string('q')->toString();

        $instituciones = Institucion::query()
            ->withCount('jurisdicciones')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/instituciones/index', [
            'instituciones' => InstitucionResource::collection($instituciones),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Institucion::class);

        return Inertia::render('maestros/instituciones/create');
    }

    public function store(StoreInstitucionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Institucion::class);

        $institucion = Institucion::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Institución \"{$institucion->nombre}\" registrada."]);

        return to_route('maestros.instituciones.index');
    }

    public function show(Institucion $institucion): Response
    {
        Gate::authorize('view', $institucion);

        $institucion->load(['jurisdicciones' => fn ($query) => $query->orderBy('codigo')]);

        return Inertia::render('maestros/instituciones/show', [
            'institucion' => new InstitucionResource($institucion),
        ]);
    }

    public function edit(Institucion $institucion): Response
    {
        Gate::authorize('update', $institucion);

        return Inertia::render('maestros/instituciones/edit', [
            'institucion' => new InstitucionResource($institucion),
        ]);
    }

    public function update(UpdateInstitucionRequest $request, Institucion $institucion): RedirectResponse
    {
        Gate::authorize('update', $institucion);

        $institucion->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Institución \"{$institucion->nombre}\" actualizada."]);

        return to_route('maestros.instituciones.show', $institucion);
    }

    public function destroy(Institucion $institucion): RedirectResponse
    {
        Gate::authorize('delete', $institucion);

        $bloqueo = $this->relacionQueImpideEliminar($institucion);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociadas."]);

            return back();
        }

        $institucion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Institución \"{$institucion->nombre}\" eliminada."]);

        return to_route('maestros.instituciones.index');
    }

    private function relacionQueImpideEliminar(Institucion $institucion): ?string
    {
        if ($institucion->jurisdicciones()->exists()) {
            return 'jurisdicciones';
        }

        return null;
    }
}
