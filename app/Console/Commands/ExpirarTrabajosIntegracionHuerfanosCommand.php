<?php

namespace App\Console\Commands;

use App\Services\Integraciones\IntegracionExternaService;
use Illuminate\Console\Command;

class ExpirarTrabajosIntegracionHuerfanosCommand extends Command
{
    protected $signature = 'trabajos-integracion:expirar-huerfanos';

    protected $description = 'Marca como huerfano todo trabajo_integracion en_progreso que supero el umbral de inactividad de su tipo';

    public function handle(IntegracionExternaService $servicio): int
    {
        $total = $servicio->expirarHuerfanos();

        $this->info("Trabajos marcados como huérfanos: {$total}");

        return self::SUCCESS;
    }
}
