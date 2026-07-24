<?php

namespace App\Http\Controllers\InformesRazonados;

use App\Http\Controllers\Controller;
use App\Http\Requests\InformesRazonados\ActualizarDefinicionInformeRazonadoRequest;
use App\Http\Requests\InformesRazonados\CrearDefinicionInformeRazonadoRequest;
use App\Http\Resources\InformesRazonados\DefinicionInformeRazonadoResource;
use App\Models\DefinicionInformeRazonado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DefinicionInformeRazonadoController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DefinicionInformeRazonado::class);

        $q = $request->string('q')->toString();

        $definiciones = DefinicionInformeRazonado::query()
            ->withCount('ejecuciones')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($sub) => $sub
                    ->where('codigo', 'like', "%{$q}%")
                    ->orWhere('nombre', 'like', "%{$q}%"),
            ))
            ->orderBy('codigo')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('informes-razonados/definiciones/index', [
            'definiciones' => DefinicionInformeRazonadoResource::collection($definiciones),
            'q' => $q !== '' ? $q : null,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', DefinicionInformeRazonado::class);

        return Inertia::render('informes-razonados/definiciones/create');
    }

    public function store(CrearDefinicionInformeRazonadoRequest $request): RedirectResponse
    {
        Gate::authorize('create', DefinicionInformeRazonado::class);

        $definicion = DefinicionInformeRazonado::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Definición \"{$definicion->nombre}\" registrada."]);

        return to_route('informes-razonados.definiciones.index');
    }

    public function show(DefinicionInformeRazonado $definicion): Response
    {
        Gate::authorize('view', $definicion);

        $definicion->load([
            'ejecuciones' => fn ($query) => $query->orderByDesc('generado_en'),
            'ejecuciones.corteReportabilidad.periodoReportabilidad',
            'ejecuciones.proceso.estadoActual',
        ]);

        return Inertia::render('informes-razonados/definiciones/show', [
            'definicion' => new DefinicionInformeRazonadoResource($definicion),
        ]);
    }

    public function edit(DefinicionInformeRazonado $definicion): Response
    {
        Gate::authorize('update', $definicion);

        return Inertia::render('informes-razonados/definiciones/edit', [
            'definicion' => new DefinicionInformeRazonadoResource($definicion),
        ]);
    }

    public function update(ActualizarDefinicionInformeRazonadoRequest $request, DefinicionInformeRazonado $definicion): RedirectResponse
    {
        Gate::authorize('update', $definicion);

        $definicion->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Definición \"{$definicion->nombre}\" actualizada."]);

        return to_route('informes-razonados.definiciones.show', $definicion);
    }

    public function destroy(DefinicionInformeRazonado $definicion): RedirectResponse
    {
        Gate::authorize('delete', $definicion);

        if ($this->relacionQueImpideEliminar($definicion) !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar: tiene ejecuciones asociadas.']);

            return back();
        }

        $definicion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Definición \"{$definicion->nombre}\" eliminada."]);

        return to_route('informes-razonados.definiciones.index');
    }

    private function relacionQueImpideEliminar(DefinicionInformeRazonado $definicion): ?string
    {
        if ($definicion->ejecuciones()->exists()) {
            return 'ejecuciones';
        }

        return null;
    }
}
