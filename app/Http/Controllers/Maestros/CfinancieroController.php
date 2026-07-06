<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreCfinancieroRequest;
use App\Http\Requests\Maestros\UpdateCfinancieroRequest;
use App\Http\Resources\Maestros\CfinancieroResource;
use App\Models\Cfinanciero;
use App\Models\Jurisdiccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CfinancieroController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Cfinanciero::class);

        $q = $request->string('q')->toString();

        $cfinancieros = Cfinanciero::query()
            ->with('jurisdiccion')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/cfinancieros/index', [
            'cfinancieros' => CfinancieroResource::collection($cfinancieros),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Cfinanciero::class);

        return Inertia::render('maestros/cfinancieros/create', $this->catalogos());
    }

    public function store(StoreCfinancieroRequest $request): RedirectResponse
    {
        Gate::authorize('create', Cfinanciero::class);

        $cfinanciero = Cfinanciero::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Centro financiero \"{$cfinanciero->nombre}\" registrado."]);

        return to_route('maestros.cfinancieros.index');
    }

    public function show(Cfinanciero $cfinanciero): Response
    {
        Gate::authorize('view', $cfinanciero);

        $cfinanciero->load('jurisdiccion');

        return Inertia::render('maestros/cfinancieros/show', [
            'cfinanciero' => new CfinancieroResource($cfinanciero),
        ]);
    }

    public function edit(Cfinanciero $cfinanciero): Response
    {
        Gate::authorize('update', $cfinanciero);

        return Inertia::render('maestros/cfinancieros/edit', [
            'cfinanciero' => new CfinancieroResource($cfinanciero),
            ...$this->catalogos(),
        ]);
    }

    public function update(UpdateCfinancieroRequest $request, Cfinanciero $cfinanciero): RedirectResponse
    {
        Gate::authorize('update', $cfinanciero);

        $cfinanciero->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Centro financiero \"{$cfinanciero->nombre}\" actualizado."]);

        return to_route('maestros.cfinancieros.show', $cfinanciero);
    }

    public function destroy(Cfinanciero $cfinanciero): RedirectResponse
    {
        Gate::authorize('delete', $cfinanciero);

        $bloqueo = $this->relacionQueImpideEliminar($cfinanciero);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociados."]);

            return back();
        }

        $cfinanciero->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Centro financiero \"{$cfinanciero->nombre}\" eliminado."]);

        return to_route('maestros.cfinancieros.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'jurisdicciones' => Jurisdiccion::all()->map(fn (Jurisdiccion $jurisdiccion) => [
                'id' => $jurisdiccion->id,
                'codigo' => $jurisdiccion->codigo,
                'nombre' => $jurisdiccion->nombre,
            ]),
        ];
    }

    private function relacionQueImpideEliminar(Cfinanciero $cfinanciero): ?string
    {
        if ($cfinanciero->ccostos()->exists()) {
            return 'centros de costo';
        }

        return null;
    }
}
