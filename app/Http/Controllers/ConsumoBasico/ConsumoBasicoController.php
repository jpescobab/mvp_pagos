<?php

namespace App\Http\Controllers\ConsumoBasico;

use App\Exceptions\ConsumoBasicoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConsumoBasico\StoreConsumoBasicoRequest;
use App\Http\Requests\ConsumoBasico\UpdateConsumoBasicoRequest;
use App\Http\Resources\ConsumoBasico\ConsumoBasicoResource;
use App\Http\Resources\PagoProveedores\CasoPagoProveedorResource;
use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\ConsumoBasico;
use App\Services\ConsumoBasico\ConsumoBasicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ConsumoBasicoController extends Controller
{
    public function create(CasoPagoProveedor $caso): Response
    {
        Gate::authorize('create', ConsumoBasico::class);

        return Inertia::render('consumo-basico/create', [
            'caso' => new CasoPagoProveedorResource($caso->load('proveedor')),
            'ccostos' => Ccosto::all(['id', 'codigo', 'nombre']),
            'clientesMedidoresProveedor' => $caso->proveedor_id === null
                ? []
                : ClienteMedidor::where('proveedor_id', $caso->proveedor_id)
                    ->get(['id', 'numero_cliente', 'tipo_suministro']),
        ]);
    }

    public function store(CasoPagoProveedor $caso, StoreConsumoBasicoRequest $request, ConsumoBasicoService $servicio): RedirectResponse
    {
        Gate::authorize('create', ConsumoBasico::class);

        $datos = $request->validated();
        $datosClienteMedidor = Arr::pull($datos, 'cliente_medidor');

        try {
            $consumoBasico = $servicio->registrar($caso, $datos, $datosClienteMedidor);
        } catch (ConsumoBasicoException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Detalle de consumo registrado.']);

        return to_route('consumo-basico.show', $consumoBasico);
    }

    public function show(ConsumoBasico $consumoBasico): Response
    {
        Gate::authorize('view', $consumoBasico);

        $consumoBasico->load(['clienteMedidor', 'casoPagoProveedor', 'vinculosDocumento.documento.versiones']);

        return Inertia::render('consumo-basico/show', [
            'consumoBasico' => new ConsumoBasicoResource($consumoBasico),
        ]);
    }

    public function edit(ConsumoBasico $consumoBasico): Response
    {
        Gate::authorize('update', $consumoBasico);

        $consumoBasico->load(['clienteMedidor']);

        return Inertia::render('consumo-basico/edit', [
            'consumoBasico' => new ConsumoBasicoResource($consumoBasico),
        ]);
    }

    public function update(UpdateConsumoBasicoRequest $request, ConsumoBasico $consumoBasico, ConsumoBasicoService $servicio): RedirectResponse
    {
        Gate::authorize('update', $consumoBasico);

        $servicio->actualizar($consumoBasico, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Detalle de consumo actualizado.']);

        return to_route('consumo-basico.show', $consumoBasico);
    }
}
