<?php

namespace App\Http\Controllers\PagoProveedores;

use App\Http\Controllers\Controller;
use App\Http\Requests\PagoProveedores\VincularAdquisicionRequest;
use App\Models\CasoPagoProveedor;
use App\Models\ProcesoAdquisicion;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VinculoAdquisicionCasoPagoProveedorController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(CasoPagoProveedor $caso, VincularAdquisicionRequest $request): RedirectResponse
    {
        Gate::authorize('vincularAdquisicion', $caso);

        $procesoAdquisicion = ProcesoAdquisicion::findOrFail($request->integer('proceso_adquisicion_id'));

        DB::transaction(function () use ($caso, $procesoAdquisicion): void {
            $procesoAdquisicionAnteriorId = $caso->proceso_adquisicion_id;

            $caso->update(['proceso_adquisicion_id' => $procesoAdquisicion->id]);

            $this->auditLogger->log(
                action: 'caso_pago_proveedor.vincular_adquisicion',
                auditable: $caso,
                before: ['proceso_adquisicion_id' => $procesoAdquisicionAnteriorId],
                after: ['proceso_adquisicion_id' => $procesoAdquisicion->id],
            );
        });

        return back();
    }

    public function destroy(CasoPagoProveedor $caso): RedirectResponse
    {
        Gate::authorize('vincularAdquisicion', $caso);

        DB::transaction(function () use ($caso): void {
            $procesoAdquisicionAnteriorId = $caso->proceso_adquisicion_id;

            $caso->update(['proceso_adquisicion_id' => null]);

            $this->auditLogger->log(
                action: 'caso_pago_proveedor.desvincular_adquisicion',
                auditable: $caso,
                before: ['proceso_adquisicion_id' => $procesoAdquisicionAnteriorId],
                after: ['proceso_adquisicion_id' => null],
            );
        });

        return back();
    }
}
