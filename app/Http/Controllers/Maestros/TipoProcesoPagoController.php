<?php

namespace App\Http\Controllers\Maestros;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maestros\StoreTipoProcesoPagoRequest;
use App\Http\Requests\Maestros\UpdateTipoProcesoPagoRequest;
use App\Http\Resources\Maestros\TipoProcesoPagoResource;
use App\Models\TipoProcesoPago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TipoProcesoPagoController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', TipoProcesoPago::class);

        $tiposProcesoPago = TipoProcesoPago::query()->orderBy('nombre')->get();

        return Inertia::render('maestros/tipos-proceso-pago/index', [
            'tiposProcesoPago' => TipoProcesoPagoResource::collection($tiposProcesoPago),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', TipoProcesoPago::class);

        return Inertia::render('maestros/tipos-proceso-pago/create');
    }

    public function store(StoreTipoProcesoPagoRequest $request): RedirectResponse
    {
        Gate::authorize('create', TipoProcesoPago::class);

        $tipoProcesoPago = TipoProcesoPago::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de proceso de pago \"{$tipoProcesoPago->nombre}\" registrado."]);

        return to_route('maestros.tipos-proceso-pago.index');
    }

    public function show(TipoProcesoPago $tipoProcesoPago): Response
    {
        Gate::authorize('view', $tipoProcesoPago);

        return Inertia::render('maestros/tipos-proceso-pago/show', [
            'tipoProcesoPago' => new TipoProcesoPagoResource($tipoProcesoPago),
        ]);
    }

    public function edit(TipoProcesoPago $tipoProcesoPago): Response
    {
        Gate::authorize('update', $tipoProcesoPago);

        return Inertia::render('maestros/tipos-proceso-pago/edit', [
            'tipoProcesoPago' => new TipoProcesoPagoResource($tipoProcesoPago),
        ]);
    }

    public function update(UpdateTipoProcesoPagoRequest $request, TipoProcesoPago $tipoProcesoPago): RedirectResponse
    {
        Gate::authorize('update', $tipoProcesoPago);

        $tipoProcesoPago->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de proceso de pago \"{$tipoProcesoPago->nombre}\" actualizado."]);

        return to_route('maestros.tipos-proceso-pago.show', $tipoProcesoPago);
    }

    public function destroy(TipoProcesoPago $tipoProcesoPago): RedirectResponse
    {
        Gate::authorize('delete', $tipoProcesoPago);

        $bloqueo = $this->relacionQueImpideEliminar($tipoProcesoPago);

        if ($bloqueo !== null) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "No se puede eliminar: tiene {$bloqueo} asociados."]);

            return back();
        }

        $tipoProcesoPago->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Tipo de proceso de pago \"{$tipoProcesoPago->nombre}\" eliminado."]);

        return to_route('maestros.tipos-proceso-pago.index');
    }

    private function relacionQueImpideEliminar(TipoProcesoPago $tipoProcesoPago): ?string
    {
        if ($tipoProcesoPago->requisitos()->exists()) {
            return 'requisitos documentales';
        }

        if ($tipoProcesoPago->procesos()->exists()) {
            return 'casos de pago de proveedores';
        }

        return null;
    }
}
