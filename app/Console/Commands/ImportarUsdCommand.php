<?php

namespace App\Console\Commands;

use App\Services\Indicadores\ServicioImportacionIndicadores;
use Illuminate\Console\Command;

class ImportarUsdCommand extends Command
{
    protected $signature = 'indicadores:importar-usd {--fecha=}';

    protected $description = 'Importa el dólar observado desde la CMF (opcionalmente reprocesa una fecha YYYY-MM-DD puntual)';

    public function handle(ServicioImportacionIndicadores $servicio): int
    {
        $fecha = $this->option('fecha');

        $importacion = $servicio->importarUsd(fecha: $fecha);

        $this->info("Importación finalizada con estado: {$importacion->estado}");
        $this->line("Recibidos: {$importacion->total_recibidos} | Creados: {$importacion->total_creados} | Omitidos: {$importacion->total_omitidos} | Fallidos: {$importacion->total_fallidos}");

        return $importacion->estado === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
