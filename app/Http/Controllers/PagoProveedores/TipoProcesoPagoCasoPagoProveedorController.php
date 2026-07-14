<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\ClasificarTipoProcesoPagoRequest;
use App\Models\CasoPagoProveedor;
use App\Models\TipoProcesoPago;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TipoProcesoPagoCasoPagoProveedorController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(CasoPagoProveedor $caso, ClasificarTipoProcesoPagoRequest $request): RedirectResponse
    {
        Gate::authorize('clasificarTipoProcesoPago', $caso);

        $tipoProcesoPago = TipoProcesoPago::findOrFail($request->integer('tipo_proceso_pago_id'));

        DB::transaction(function () use ($caso, $tipoProcesoPago): void {
            $tipoProcesoPagoAnteriorId = $caso->proceso->tipo_proceso_pago_id;

            $caso->proceso->update(['tipo_proceso_pago_id' => $tipoProcesoPago->id]);

            $this->auditLogger->log(
                action: 'caso_pago_proveedor.clasificar_tipo_proceso_pago',
                auditable: $caso,
                before: ['tipo_proceso_pago_id' => $tipoProcesoPagoAnteriorId],
                after: ['tipo_proceso_pago_id' => $tipoProcesoPago->id],
            );
        });

        return back();
    }
}
