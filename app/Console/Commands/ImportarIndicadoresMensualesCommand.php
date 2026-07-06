<?php

namespace App\Console\Commands;

use App\Services\Indicadores\ServicioImportacionIndicadores;
use Illuminate\Console\Command;

class ImportarIndicadoresMensualesCommand extends Command
{
    protected $signature = 'indicadores:importar-mensual {--periodo=}';

    protected $description = 'Importa UF, UTM, UTA e IPC desde la CMF (opcionalmente reprocesa un periodo YYYY-MM puntual)';

    public function handle(ServicioImportacionIndicadores $servicio): int
    {
        $periodo = $this->option('periodo');

        $importacion = $servicio->importarMensual(periodo: $periodo);

        $this->info("Importación finalizada con estado: {$importacion->estado}");
        $this->line("Recibidos: {$importacion->total_recibidos} | Creados: {$importacion->total_creados} | Omitidos: {$importacion->total_omitidos} | Fallidos: {$importacion->total_fallidos}");

        return $importacion->estado === 'failed' ? self::FAILURE : self::SUCCESS;
    }
}
