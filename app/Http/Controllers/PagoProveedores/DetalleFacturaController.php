<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Exceptions\DetalleFacturaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\StoreDetalleFacturaRequest;
use App\Http\Requests\PagoProveedores\UpdateDetalleFacturaRequest;
use App\Http\Resources\PagoProveedores\DetalleFacturaResource;
use App\Models\Ccosto;
use App\Models\Factura;
use App\Models\TipoCompra;
use App\Services\PagoProveedores\DetalleFacturaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DetalleFacturaController extends Controller
{
    public function create(Factura $factura): Response
    {
        Gate::authorize('registrarDetalleFactura', $factura->caso);

        return Inertia::render('pago-proveedores/detalle-factura/create', [
            'factura' => [
                'id' => $factura->id,
                'folio' => $factura->folio,
                'monto' => $factura->monto,
                'caso_pago_proveedor_id' => $factura->caso_pago_proveedor_id,
            ],
            ...$this->catalogos(),
        ]);
    }

    public function store(Factura $factura, StoreDetalleFacturaRequest $request, DetalleFacturaService $servicio): RedirectResponse
    {
        Gate::authorize('registrarDetalleFactura', $factura->caso);

        try {
            $servicio->registrar($factura, $request->validated());
        } catch (DetalleFacturaException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Detalle de factura registrado.']);

        return to_route('pago-proveedores.casos.show', $factura->caso_pago_proveedor_id);
    }

    public function edit(Factura $factura): Response|RedirectResponse
    {
        Gate::authorize('registrarDetalleFactura', $factura->caso);

        $factura->load('detalle');

        // Puede llegarse acá sin detalle todavía (URL directa, o un
        // `caso.facturas` desactualizado en el frontend) — se manda a crear
        // en vez de crashear construyendo el Resource con un recurso null.
        if ($factura->detalle === null) {
            return to_route('pago-proveedores.facturas.detalle-factura.create', $factura);
        }

        return Inertia::render('pago-proveedores/detalle-factura/edit', [
            'factura' => [
                'id' => $factura->id,
                'folio' => $factura->folio,
                'monto' => $factura->monto,
                'caso_pago_proveedor_id' => $factura->caso_pago_proveedor_id,
            ],
            'detalleFactura' => new DetalleFacturaResource($factura->detalle),
            ...$this->catalogos(),
        ]);
    }

    public function update(UpdateDetalleFacturaRequest $request, Factura $factura, DetalleFacturaService $servicio): RedirectResponse
    {
        Gate::authorize('registrarDetalleFactura', $factura->caso);

        $detalle = $factura->detalle;

        if ($detalle === null) {
            return back()->withErrors(['factura' => DetalleFacturaException::sinDetalleAun()->getMessage()]);
        }

        $servicio->actualizar($detalle, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Detalle de factura actualizado.']);

        return to_route('pago-proveedores.casos.show', $factura->caso_pago_proveedor_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'tiposCompra' => TipoCompra::where('activo', true)->get(['id', 'nombre']),
            'ccostos' => Ccosto::all(['id', 'codigo', 'nombre']),
        ];
    }
}
