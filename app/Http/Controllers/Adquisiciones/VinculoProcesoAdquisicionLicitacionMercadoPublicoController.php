<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\VincularLicitacionMercadoPublicoRequest;
use App\Models\LicitacionMercadoPublico;
use App\Models\ProcesoAdquisicion;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VinculoProcesoAdquisicionLicitacionMercadoPublicoController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(LicitacionMercadoPublico $licitacion, VincularLicitacionMercadoPublicoRequest $request): RedirectResponse
    {
        Gate::authorize('vincularProcesoAdquisicion', $licitacion);

        $procesoAdquisicion = ProcesoAdquisicion::findOrFail($request->integer('proceso_adquisicion_id'));

        DB::transaction(function () use ($licitacion, $procesoAdquisicion): void {
            $procesoAdquisicionAnteriorId = $licitacion->proceso_adquisicion_id;

            $licitacion->update(['proceso_adquisicion_id' => $procesoAdquisicion->id]);

            $this->auditLogger->log(
                action: 'licitacion_mercado_publico.vincular_proceso_adquisicion',
                auditable: $licitacion,
                before: ['proceso_adquisicion_id' => $procesoAdquisicionAnteriorId],
                after: ['proceso_adquisicion_id' => $procesoAdquisicion->id],
            );
        });

        return back();
    }

    public function destroy(LicitacionMercadoPublico $licitacion): RedirectResponse
    {
        Gate::authorize('vincularProcesoAdquisicion', $licitacion);

        DB::transaction(function () use ($licitacion): void {
            $procesoAdquisicionAnteriorId = $licitacion->proceso_adquisicion_id;

            $licitacion->update(['proceso_adquisicion_id' => null]);

            $this->auditLogger->log(
                action: 'licitacion_mercado_publico.desvincular_proceso_adquisicion',
                auditable: $licitacion,
                before: ['proceso_adquisicion_id' => $procesoAdquisicionAnteriorId],
                after: ['proceso_adquisicion_id' => null],
            );
        });

        return back();
    }
}
