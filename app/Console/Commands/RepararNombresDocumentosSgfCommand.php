<?php

namespace App\Console\Commands;

use App\Services\Sgf\ReparadorDocumentosSgfExistentes;
use Illuminate\Console\Command;

class RepararNombresDocumentosSgfCommand extends Command
{
    protected $signature = 'sgf:reparar-nombres-documentos {--dry-run : Lista lo que se repararía sin modificar la base de datos ni los archivos}';

    protected $description = 'Repara los nombres con mojibake (UTF-8 leído como Latin-1) de documentos SGF ya importados y renombra sus archivos';

    public function handle(ReparadorDocumentosSgfExistentes $reparador): int
    {
        $resultados = $reparador->reparar((bool) $this->option('dry-run'));
        $irreparables = $reparador->irreparables();

        if ($irreparables !== []) {
            $this->warn('Nombres que siguen corruptos y no se pueden reparar automáticamente (revisar a mano):');
            $this->table(
                ['Documento', 'Nombre'],
                array_map(fn (array $fila): array => [$fila['documento_id'], $fila['nombre']], $irreparables),
            );
        }

        if ($resultados === []) {
            $this->info('No hay nombres de documentos SGF para reparar.');

            return self::SUCCESS;
        }

        $this->table(
            ['Documento', 'Nombre antes', 'Nombre después', 'Resultado'],
            array_map(
                fn (array $fila): array => [$fila['documento_id'], $fila['nombre_antes'], $fila['nombre_despues'], $fila['resultado']],
                $resultados,
            ),
        );

        $conflictos = count(array_filter(
            $resultados,
            fn (array $fila): bool => $fila['resultado'] === ReparadorDocumentosSgfExistentes::RESULTADO_CONFLICTO,
        ));

        if ($conflictos > 0) {
            $this->warn("{$conflictos} documento(s) omitido(s) porque el archivo reparado ya existe en el destino: revisar a mano.");
        }

        return self::SUCCESS;
    }
}
