<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\RegistrarFacturaRequest;
use App\Models\CasoPagoProveedor;
use App\Models\Factura;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FacturaController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(CasoPagoProveedor $caso, RegistrarFacturaRequest $request): RedirectResponse
    {
        Gate::authorize('registrarFactura', $caso);

        DB::transaction(function () use ($caso, $request): void {
            $factura = Factura::create([
                'caso_pago_proveedor_id' => $caso->id,
                'proveedor_id' => $caso->proveedor_id,
                'folio' => $request->string('folio')->toString(),
                'monto' => $request->float('monto'),
                'fecha_emision' => $request->date('fecha_emision'),
            ]);

            $this->auditLogger->log(
                action: 'caso_pago_proveedor.registrar_factura',
                auditable: $caso,
                after: $factura->only(['folio', 'monto', 'fecha_emision']),
            );
        });

        return back();
    }
}
