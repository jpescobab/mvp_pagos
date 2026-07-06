<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreCcostoRequest;
use App\Http\Requests\Maestros\UpdateCcostoRequest;
use App\Http\Resources\Maestros\CcostoResource;
use App\Models\Ccosto;
use App\Models\Cfinanciero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CcostoController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Ccosto::class);

        $q = $request->string('q')->toString();

        $ccostos = Ccosto::query()
            ->with('cfinanciero')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('maestros/ccostos/index', [
            'ccostos' => CcostoResource::collection($ccostos),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Ccosto::class);

        return Inertia::render('maestros/ccostos/create', $this->catalogos());
    }

    public function store(StoreCcostoRequest $request): RedirectResponse
    {
        Gate::authorize('create', Ccosto::class);

        $ccosto = Ccosto::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Centro de costo \"{$ccosto->nombre}\" registrado."]);

        return to_route('maestros.ccostos.index');
    }

    public function show(Ccosto $ccosto): Response
    {
        Gate::authorize('view', $ccosto);

        $ccosto->load('cfinanciero');

        return Inertia::render('maestros/ccostos/show', [
            'ccosto' => new CcostoResource($ccosto),
        ]);
    }

    public function edit(Ccosto $ccosto): Response
    {
        Gate::authorize('update', $ccosto);

        return Inertia::render('maestros/ccostos/edit', [
            'ccosto' => new CcostoResource($ccosto),
            ...$this->catalogos(),
        ]);
    }

    public function update(UpdateCcostoRequest $request, Ccosto $ccosto): RedirectResponse
    {
        Gate::authorize('update', $ccosto);

        $ccosto->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Centro de costo \"{$ccosto->nombre}\" actualizado."]);

        return to_route('maestros.ccostos.show', $ccosto);
    }

    public function destroy(Ccosto $ccosto): RedirectResponse
    {
        Gate::authorize('delete', $ccosto);

        $bloqueo = $this->relacionQueImpideEliminar($ccosto);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociados."]);

            return back();
        }

        $ccosto->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Centro de costo \"{$ccosto->nombre}\" eliminado."]);

        return to_route('maestros.ccostos.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'cfinancieros' => Cfinanciero::all()->map(fn (Cfinanciero $cfinanciero) => [
                'id' => $cfinanciero->id,
                'codigo' => $cfinanciero->codigo,
                'nombre' => $cfinanciero->nombre,
            ]),
        ];
    }

    private function relacionQueImpideEliminar(Ccosto $ccosto): ?string
    {
        if ($ccosto->clienteMedidores()->exists()) {
            return 'clientes medidores';
        }

        if ($ccosto->procesosAdquisicion()->exists()) {
            return 'procesos de adquisición';
        }

        return null;
    }
}
