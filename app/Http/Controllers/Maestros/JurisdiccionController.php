<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreJurisdiccionRequest;
use App\Http\Requests\Maestros\UpdateJurisdiccionRequest;
use App\Http\Resources\Maestros\JurisdiccionResource;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JurisdiccionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Jurisdiccion::class);

        $q = $request->string('q')->toString();

        $jurisdicciones = Jurisdiccion::query()
            ->with('institucion')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/jurisdicciones/index', [
            'jurisdicciones' => JurisdiccionResource::collection($jurisdicciones),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Jurisdiccion::class);

        return Inertia::render('maestros/jurisdicciones/create', $this->catalogos());
    }

    public function store(StoreJurisdiccionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Jurisdiccion::class);

        $jurisdiccion = Jurisdiccion::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Jurisdicción \"{$jurisdiccion->nombre}\" registrada."]);

        return to_route('maestros.jurisdicciones.index');
    }

    public function show(Jurisdiccion $jurisdiccion): Response
    {
        Gate::authorize('view', $jurisdiccion);

        $jurisdiccion->load([
            'institucion',
            'cfinancieros' => fn ($query) => $query->orderBy('codigo'),
        ]);

        return Inertia::render('maestros/jurisdicciones/show', [
            'jurisdiccion' => new JurisdiccionResource($jurisdiccion),
        ]);
    }

    public function edit(Jurisdiccion $jurisdiccion): Response
    {
        Gate::authorize('update', $jurisdiccion);

        $jurisdiccion->load('institucion');

        return Inertia::render('maestros/jurisdicciones/edit', [
            'jurisdiccion' => new JurisdiccionResource($jurisdiccion),
            ...$this->catalogos(),
        ]);
    }

    public function update(UpdateJurisdiccionRequest $request, Jurisdiccion $jurisdiccion): RedirectResponse
    {
        Gate::authorize('update', $jurisdiccion);

        $jurisdiccion->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Jurisdicción \"{$jurisdiccion->nombre}\" actualizada."]);

        return to_route('maestros.jurisdicciones.show', $jurisdiccion);
    }

    public function destroy(Jurisdiccion $jurisdiccion): RedirectResponse
    {
        Gate::authorize('delete', $jurisdiccion);

        $bloqueo = $this->relacionQueImpideEliminar($jurisdiccion);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociados."]);

            return back();
        }

        $jurisdiccion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Jurisdicción \"{$jurisdiccion->nombre}\" eliminada."]);

        return to_route('maestros.jurisdicciones.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'instituciones' => Institucion::query()
                ->where('activo', true)
                ->orderBy('codigo')
                ->get()
                ->map(fn (Institucion $institucion) => [
                    'id' => $institucion->id,
                    'codigo' => $institucion->codigo,
                    'nombre' => $institucion->nombre,
                ]),
        ];
    }

    private function relacionQueImpideEliminar(Jurisdiccion $jurisdiccion): ?string
    {
        if ($jurisdiccion->cfinancieros()->exists()) {
            return 'centros financieros';
        }

        return null;
    }
}
