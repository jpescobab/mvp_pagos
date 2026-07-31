<?php

namespace App\Console\Commands;

use App\Services\Indicadores\ServicioImportacionIndicadores;
use Illuminate\Console\Command;

class ImportarUsdCommand extends Command
{
    protected $signature = 'indicadores:importar-usd {--fecha=} {--periodo=}';

    protected $description = 'Importa el dólar observado desde la CMF (--fecha reprocesa un día puntual YYYY-MM-DD, --periodo trae todo un mes YYYY-MM para backfill)';

    public function handle(ServicioImportacionIndicadores $servicio): int
    {
        $periodo = $this->option('periodo');

        $importacion = $periodo !== null
            ? $servicio->importarUsdDelMes($periodo)
            : $servicio->importarUsd(fecha: $this->option('fecha'));

        $this->info("Importación finalizada con estado: {$importacion->estado}");
        $this->line("Recibidos: {$importacion->total_recibidos} | Creados: {$importacion->total_creados} | Omitidos: {$importacion->total_omitidos} | Fallidos: {$importacion->total_fallidos}");

        return $importacion->estado === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
