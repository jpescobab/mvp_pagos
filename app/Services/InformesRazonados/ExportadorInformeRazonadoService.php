<?php

namespace App\Services\InformesRazonados;

use App\Models\EjecucionInformeRazonado;
use Barryvdh\DomPDF\Facade\Pdf;
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
    public const FORMATOS_SOPORTADOS = ['html', 'pdf'];

    /**
     * Genera el archivo de exportación de una ejecución en el formato indicado,
     * lo guarda en almacenamiento privado y devuelve su ruta relativa.
     */
    public function exportar(EjecucionInformeRazonado $ejecucion, string $formato): string
    {
        return match ($formato) {
            'html' => $this->exportarHtml($ejecucion),
            'pdf' => $this->exportarPdf($ejecucion),
            default => throw new InvalidArgumentException("Formato de exportación no soportado: {$formato}."),
        };
    }

    private function exportarHtml(EjecucionInformeRazonado $ejecucion): string
    {
        $html = $this->render($ejecucion);

        $ruta = $this->rutaPara($ejecucion, 'html');

        Storage::disk('local')->put($ruta, $html);

        return $ruta;
    }

    private function exportarPdf(EjecucionInformeRazonado $ejecucion): string
    {
        $pdf = Pdf::loadHTML($this->render($ejecucion));

        $ruta = $this->rutaPara($ejecucion, 'pdf');

        Storage::disk('local')->put($ruta, $pdf->output());

        return $ruta;
    }

    /**
     * Renderiza la vista del informe a HTML poblado. Reutilizada por los
     * formatos HTML y PDF para garantizar contenido idéntico.
     */
    private function render(EjecucionInformeRazonado $ejecucion): string
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

        return View::make('informes-razonados.export.html', [
            'ejecucion' => $ejecucion,
        ])->render();
    }

    private function rutaPara(EjecucionInformeRazonado $ejecucion, string $extension): string
    {
        return "informes-razonados/{$ejecucion->id}/informe-{$ejecucion->id}-".now()->format('Ymd-His').".{$extension}";
    }
}
