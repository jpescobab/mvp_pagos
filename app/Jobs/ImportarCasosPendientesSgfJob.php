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
        return [new WithoutOverlapping('sgf-importar-pendientes')];
    }
}
