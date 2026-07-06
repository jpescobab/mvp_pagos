<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\VincularOrdenCompraMercadoPublicoRequest;
use App\Models\OrdenCompraMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VinculoProcesoAdquisicionOrdenCompraMercadoPublicoController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(OrdenCompraMercadoPublico $orden, VincularOrdenCompraMercadoPublicoRequest $request): RedirectResponse
    {
        Gate::authorize('vincularProcesoAdquisicion', $orden);

        $procesoAdquisicion = ProcesoAdquisicion::findOrFail($request->integer('proceso_adquisicion_id'));

        DB::transaction(function () use ($orden, $procesoAdquisicion): void {
            $procesoAdquisicionAnteriorId = $orden->proceso_adquisicion_id;

            $orden->update(['proceso_adquisicion_id' => $procesoAdquisicion->id]);

            $this->auditLogger->log(
                action: 'orden_compra_mercado_publico.vincular_proceso_adquisicion',
                auditable: $orden,
                before: ['proceso_adquisicion_id' => $procesoAdquisicionAnteriorId],
                after: ['proceso_adquisicion_id' => $procesoAdquisicion->id],
            );
        });

        return back();
    }

    public function destroy(OrdenCompraMercadoPublico $orden): RedirectResponse
    {
        Gate::authorize('vincularProcesoAdquisicion', $orden);

        DB::transaction(function () use ($orden): void {
            $procesoAdquisicionAnteriorId = $orden->proceso_adquisicion_id;

            $orden->update(['proceso_adquisicion_id' => null]);

            $this->auditLogger->log(
                action: 'orden_compra_mercado_publico.desvincular_proceso_adquisicion',
                auditable: $orden,
                before: ['proceso_adquisicion_id' => $procesoAdquisicionAnteriorId],
                after: ['proceso_adquisicion_id' => null],
            );
        });

        return back();
    }
}
