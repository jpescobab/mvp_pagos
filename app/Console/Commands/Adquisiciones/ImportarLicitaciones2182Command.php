<?php

namespace App\Console\Commands\Adquisiciones;

use App\Services\Adquisiciones\LicitacionMercadoPublicoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('adquisiciones:importar-licitaciones-2182 {--desde=1 : Correlativo inicial} {--hasta=140 : Correlativo final} {--sufijos=L126,LE26 : Sufijos a probar en orden para cada correlativo} {--prefijo=2182 : Prefijo del código de licitación}')]
#[Description('Importa desde Mercado Público las licitaciones 2182-{correlativo}-{sufijo}, probando cada sufijo hasta encontrar una coincidencia')]
class ImportarLicitaciones2182Command extends Command
{
    public function __construct(private readonly LicitacionMercadoPublicoService $servicio)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $desde = (int) $this->option('desde');
        $hasta = (int) $this->option('hasta');
        $prefijo = (string) $this->option('prefijo');
        $sufijos = array_filter(array_map('trim', explode(',', (string) $this->option('sufijos'))));

        $guardadas = 0;
        $yaExistentes = 0;
        $noEncontradas = [];

        for ($correlativo = $desde; $correlativo <= $hasta; $correlativo++) {
            $resultado = $this->procesarCorrelativo($prefijo, $correlativo, $sufijos);

            match ($resultado) {
                'guardada' => $guardadas++,
                'ya_existente' => $yaExistentes++,
                default => $noEncontradas[] = "{$prefijo}-{$correlativo}-*",
            };
        }

        $this->newLine();
        $this->info("Guardadas: {$guardadas}. Ya existentes: {$yaExistentes}. Sin coincidencia: ".count($noEncontradas).'.');

        if ($noEncontradas !== []) {
            $this->warn('Correlativos sin ninguna licitación encontrada: '.implode(', ', $noEncontradas));
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $sufijos
     */
    private function procesarCorrelativo(string $prefijo, int $correlativo, array $sufijos): string
    {
        foreach ($sufijos as $sufijo) {
            $codigo = "{$prefijo}-{$correlativo}-{$sufijo}";

            if ($this->servicio->buscarLocal($codigo) !== null) {
                $this->line("<comment>{$codigo}</comment>: ya existe en la base de datos, se omite.");

                return 'ya_existente';
            }

            $resultado = $this->servicio->consultarApi($codigo);

            if (! $resultado['encontrada']) {
                continue;
            }

            $this->servicio->guardarDesdeApi($resultado['payload_normalizado'], $resultado['snapshot']);

            $this->info("{$codigo}: guardada.");

            return 'guardada';
        }

        $this->line("<fg=gray>{$prefijo}-{$correlativo}-*: sin coincidencia en ninguno de los sufijos probados.</>");

        return 'sin_coincidencia';
    }
}
