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

class ImportarCasosPendientesSgfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Recorrer todos los procesos pendientes de la Bandeja en SGF (navegación
     * real + popup y descarga por cada documento adjunto) puede tardar mucho
     * más que 60s — 64 procesos ya demostraron superarlo largamente.
     *
     * IMPORTANTE (VERIFICADO 2026-07-08): esta propiedad por sí sola NO evita
     * que el job muera a los 60s bajo `queue:listen`. Ese comando ejecuta
     * cada job en un proceso hijo separado envuelto en un
     * `Symfony\Component\Process\Process` que tiene su PROPIO timeout de 60s
     * — independiente de este `$timeout` (que solo controla el límite interno
     * vía pcntl_alarm, no disponible en Windows de todos modos). Cuando el
     * Symfony Process externo vence, mata el proceso hijo Y HACE CRASHEAR a
     * `queue:listen` completo (ProcessTimedOutException sin capturar), sin
     * dejar ningún registro en `trabajos_integracion`. El fix real es pasar
     * `--timeout=3700` al propio `queue:listen` (ver composer.json, script
     * "dev"). Esta propiedad se mantiene como límite interno adicional para
     * cuando el job corre bajo `queue:work` (sin el wrapper de Process).
     */
    public int $timeout = 3600;

    public function __construct(private readonly TrabajoIntegracion $trabajo) {}

    public function handle(ConectorSgfPlaywrightService $conectorSgf): void
    {
        $conectorSgf->importarPendientes($this->trabajo);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        // expireAfter() es obligatorio: sin él, WithoutOverlapping deja el
        // lock tomado ~24h por defecto. Si este proceso muere de forma
        // abrupta (timeout del worker, terminal cerrada) sin alcanzar a
        // liberarlo, cada intento nuevo choca contra el lock viejo, se
        // auto-libera con release(0), y al recogerse de nuevo ya cuenta como
        // "intento agotado" (tries=1) — MaxAttemptsExceededException sin que
        // el handle() llegue a ejecutar nunca. VERIFICADO (2026-07-08): esto
        // pasó dos veces seguidas antes de fijar este valor. Un poco por
        // encima de $timeout para no expirar el lock mientras el job sigue
        // legítimamente corriendo.
        return [(new WithoutOverlapping('sgf-importar-pendientes'))->expireAfter(3700)];
    }
}
