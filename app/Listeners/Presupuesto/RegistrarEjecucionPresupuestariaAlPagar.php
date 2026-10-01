<?php

namespace App\Listeners\Presupuesto;

use App\Events\TransicionWorkflowEjecutada;
use App\Models\CasoPagoProveedor;
use App\Services\AuditLogger;
use App\Services\Presupuesto\RegistrarEjecucionDesdePagoService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reacciona al pago de un caso (`marcar_pagada_bancoestado`). Una falla acá
 * nunca revierte ni impide el pago ya confirmado: se audita y se registra en
 * log para reproceso (el service es idempotente).
 */
class RegistrarEjecucionPresupuestariaAlPagar
{
    public function __construct(
        private readonly RegistrarEjecucionDesdePagoService $service,
        private readonly AuditLogger $auditoria,
    ) {}

    public function handle(TransicionWorkflowEjecutada $evento): void
    {
        if ($evento->transicionCodigo !== 'marcar_pagada_bancoestado') {
            return;
        }

        $caso = $evento->proceso->sujeto;

        if (! $caso instanceof CasoPagoProveedor) {
            return;
        }

        try {
            $this->service->registrar($caso, $evento->user);
        } catch (Throwable $e) {
            Log::error('presupuesto.ejecucion_desde_pago_fallida', [
                'caso_pago_proveedor_id' => $caso->id,
                'error' => $e->getMessage(),
            ]);

            try {
                $this->auditoria->log(
                    action: 'presupuesto.ejecucion_fallida',
                    auditable: $caso,
                    after: ['error' => $e->getMessage()],
                    user: $evento->user,
                );
            } catch (Throwable) {
                // La constancia en log ya quedó registrada arriba.
            }
        }
    }
}
