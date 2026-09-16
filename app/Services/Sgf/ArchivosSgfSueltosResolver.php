<?php

namespace App\Services\Sgf;

use App\Models\CasoPagoProveedor;
use App\Models\VersionDocumento;
use Illuminate\Support\Facades\Storage;

/**
 * Detecta archivos que el scraper SGF dejó escritos en
 * storage/app/private/sgf-documentos/{sgfId}/ pero que nunca llegaron a
 * reportarse de vuelta a Laravel (ver sgf-scraper.js::descargarDocumentosDeFila,
 * el writeFile() ocurre antes del push() al array de retorno) — quedan
 * huérfanos en el filesystem, sin ninguna fila VersionDocumento asociada.
 * Todo CasoPagoProveedor tiene sgf_id (columna NOT NULL, es la vía por la
 * que se importa desde SGF), así que no hace falta contemplar su ausencia.
 */
class ArchivosSgfSueltosResolver
{
    /**
     * @return list<array{ruta_archivo: string, nombre_archivo: string}>
     */
    public function disponibles(CasoPagoProveedor $caso): array
    {
        $archivosEnDisco = Storage::disk('local')->files("sgf-documentos/{$caso->sgf_id}");

        if ($archivosEnDisco === []) {
            return [];
        }

        $rutasYaRegistradas = VersionDocumento::whereIn('ruta_archivo', $archivosEnDisco)
            ->pluck('ruta_archivo')
            ->all();

        return array_values(collect($archivosEnDisco)
            ->reject(fn (string $ruta) => in_array($ruta, $rutasYaRegistradas, true))
            ->map(fn (string $ruta) => [
                'ruta_archivo' => $ruta,
                'nombre_archivo' => basename($ruta),
            ])
            ->all());
    }
}
