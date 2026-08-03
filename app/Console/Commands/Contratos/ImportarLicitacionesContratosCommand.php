<?php

namespace App\Console\Commands\Contratos;

use App\Models\Contrato;
use App\Services\Adquisiciones\LicitacionMercadoPublicoService;
use App\Services\Contratos\ContratoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contratos:importar-licitaciones')]
#[Description('Importa desde Mercado Público las licitaciones referenciadas en el campo id_proceso_mp de los contratos existentes (excluyendo fuera_de_portal) y las vincula al contrato correspondiente')]
class ImportarLicitacionesContratosCommand extends Command
{
    public function __construct(
        private readonly LicitacionMercadoPublicoService $licitaciones,
        private readonly ContratoService $contratos,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $codigos = Contrato::where('modalidad_compra', '!=', 'fuera_de_portal')
            ->whereNotNull('id_proceso_mp')
            ->distinct()
            ->pluck('id_proceso_mp');

        $importadas = 0;
        $yaExistentes = 0;
        $sinCoincidencia = [];
        $vinculadas = 0;

        foreach ($codigos as $codigo) {
            $licitacion = $this->licitaciones->buscarLocal($codigo);

            if ($licitacion === null) {
                $resultado = $this->licitaciones->consultarApi($codigo);

                if (! $resultado['encontrada']) {
                    $this->line("<fg=gray>{$codigo}: sin coincidencia en Mercado Público.</>");
                    $sinCoincidencia[] = $codigo;

                    continue;
                }

                $guardado = $this->licitaciones->guardarDesdeApi($resultado['payload_normalizado'], $resultado['snapshot']);
                $licitacion = $guardado['licitacion'];
                $importadas++;
                $this->info("{$codigo}: importada.");
            } else {
                $yaExistentes++;
                $this->line("<comment>{$codigo}</comment>: ya existía localmente.");
            }

            $contratosDelCodigo = Contrato::where('id_proceso_mp', $codigo)
                ->whereNull('licitacion_mercado_publico_id')
                ->get();

            foreach ($contratosDelCodigo as $contrato) {
                $this->contratos->vincularLicitacionMercadoPublico($contrato, $licitacion->id);
                $vinculadas++;
            }
        }

        $this->newLine();
        $this->info("Importadas: {$importadas}. Ya existentes: {$yaExistentes}. Contratos vinculados: {$vinculadas}. Sin coincidencia: ".count($sinCoincidencia).'.');

        if ($sinCoincidencia !== []) {
            $this->warn('Códigos sin coincidencia: '.implode(', ', $sinCoincidencia));
        }

        return self::SUCCESS;
    }
}
