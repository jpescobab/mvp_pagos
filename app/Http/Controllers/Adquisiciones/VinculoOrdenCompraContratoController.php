<?php

namespace App\Http\Controllers\Adquisiciones;

use App\Http\Controllers\Controller;
use App\Http\Requests\Adquisiciones\VincularContratoOrdenCompraMercadoPublicoRequest;
use App\Models\Contrato;
use App\Models\OrdenCompraMercadoPublico;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class VinculoOrdenCompraContratoController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function store(OrdenCompraMercadoPublico $orden, VincularContratoOrdenCompraMercadoPublicoRequest $request): RedirectResponse
    {
        Gate::authorize('vincularContrato', $orden);

        $contrato = Contrato::findOrFail($request->integer('contrato_id'));

        DB::transaction(function () use ($orden, $contrato): void {
            $contratoAnteriorId = $orden->contrato_id;

            $orden->update(['contrato_id' => $contrato->id]);

            $this->auditLogger->log(
                action: 'orden_compra_mercado_publico.vincular_contrato',
                auditable: $orden,
                before: ['contrato_id' => $contratoAnteriorId],
                after: ['contrato_id' => $contrato->id],
            );
        });

        return back();
    }

    public function destroy(OrdenCompraMercadoPublico $orden): RedirectResponse
    {
        Gate::authorize('vincularContrato', $orden);

        DB::transaction(function () use ($orden): void {
            $contratoAnteriorId = $orden->contrato_id;

            $orden->update(['contrato_id' => null]);

            $this->auditLogger->log(
                action: 'orden_compra_mercado_publico.desvincular_contrato',
                auditable: $orden,
                before: ['contrato_id' => $contratoAnteriorId],
                after: ['contrato_id' => null],
            );
        });

        return back();
    }
}
