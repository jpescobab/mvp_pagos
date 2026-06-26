<?php

namespace App\Jobs;

use App\Services\Indicadores\IndicadorEconomicoImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportarDolarDiarioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(IndicadorEconomicoImporter $importer): void
    {
        $importer->importarDolarDiario();
    }
}
