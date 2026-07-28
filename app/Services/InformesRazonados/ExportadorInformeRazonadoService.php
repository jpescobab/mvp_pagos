<?php

namespace App\Services\InformesRazonados;

use App\Models\EjecucionInformeRazonado;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;

class ExportadorInformeRazonadoService
{
    /**
     * Formatos de exportación soportados en este alcance.
     *
     * @var array<int, string>
     */
    public const FORMATOS_SOPORTADOS = ['html'];

    /**
     * Genera el archivo de exportación de una ejecución en el formato indicado,
     * lo guarda en almacenamiento privado y devuelve su ruta relativa.
     */
    public function exportar(EjecucionInformeRazonado $ejecucion, string $formato): string
    {
        if (! in_array($formato, self::FORMATOS_SOPORTADOS, true)) {
            throw new InvalidArgumentException("Formato de exportación no soportado: {$formato}.");
        }

        return $this->exportarHtml($ejecucion);
    }

    private function exportarHtml(EjecucionInformeRazonado $ejecucion): string
    {
        $ejecucion->load([
            'definicionInformeRazonado',
            'corteReportabilidad',
            'secciones',
            'metricas',
            'graficos',
            'excepciones',
            'narrativas',
        ]);

        $html = View::make('informes-razonados.export.html', [
            'ejecucion' => $ejecucion,
        ])->render();

        $ruta = "informes-razonados/{$ejecucion->id}/informe-{$ejecucion->id}-".now()->format('Ymd-His').'.html';

        Storage::disk('local')->put($ruta, $html);

        return $ruta;
    }
}
