<?php

namespace App\Jobs;

use App\Services\Indicadores\ServicioImportacionIndicadores;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ImportarDolarDiarioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ServicioImportacionIndicadores $servicio): void
    {
        $servicio->importarUsd(ejecutadoPorJob: self::class);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('indicadores-usd')];
    }
}
