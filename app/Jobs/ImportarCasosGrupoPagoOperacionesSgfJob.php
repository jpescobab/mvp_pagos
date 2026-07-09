<?php

namespace App\Jobs;

use App\Models\TrabajoIntegracion;
use App\Services\Sgf\ConectorSgfPlaywrightService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ImportarCasosGrupoPagoOperacionesSgfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Mismo límite que ImportarCasosPendientesSgfJob y misma advertencia:
     * bajo `queue:listen` el timeout real lo impone `--timeout=3700` en el
     * propio comando (ver composer.json, script "dev"), no esta propiedad.
     */
    public int $timeout = 3600;

    public function __construct(private readonly TrabajoIntegracion $trabajo) {}

    public function handle(ConectorSgfPlaywrightService $conectorSgf): void
    {
        $conectorSgf->importarGrupoPagoOperaciones($this->trabajo);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        // Lock independiente del de ImportarCasosPendientesSgfJob
        // ('sgf-importar-pendientes'): ambas importaciones deben poder
        // correr en paralelo sin bloquearse entre sí (Decisión 3,
        // design.md del change importar-casos-grupo-pago-operaciones-sgf).
        return [(new WithoutOverlapping('sgf-importar-grupo-pago-operaciones'))->expireAfter(3700)];
    }
}
