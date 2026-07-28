<?php

namespace App\Services\InformesRazonados;

use App\Models\EjecucionInformeRazonado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html as WordHtml;

class ExportadorInformeRazonadoService
{
    /**
     * Formatos de exportación soportados en este alcance.
     *
     * @var array<int, string>
     */
    public const FORMATOS_SOPORTADOS = ['html', 'pdf', 'docx', 'xlsx'];

    /**
     * Genera el archivo de exportación de una ejecución en el formato indicado,
     * lo guarda en almacenamiento privado y devuelve su ruta relativa.
     */
    public function exportar(EjecucionInformeRazonado $ejecucion, string $formato): string
    {
        return match ($formato) {
            'html' => $this->exportarHtml($ejecucion),
            'pdf' => $this->exportarPdf($ejecucion),
            'docx' => $this->exportarDocx($ejecucion),
            'xlsx' => $this->exportarXlsx($ejecucion),
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
     * Genera un Word (.docx) a partir de la misma vista renderizada del HTML,
     * de modo que refleje idéntico contenido.
     */
    private function exportarDocx(EjecucionInformeRazonado $ejecucion): string
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        WordHtml::addHtml($section, $this->cuerpoHtml($ejecucion), false, false);

        $ruta = $this->rutaPara($ejecucion, 'docx');
        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');

        $this->guardarDesdeArchivoTemporal(fn (string $tmp) => $writer->save($tmp), $ruta);

        return $ruta;
    }

    /**
     * Genera un Excel (.xlsx) tabular desde el modelo: metadata + hoja de
     * métricas y hoja de excepciones. La narrativa libre no se vuelca a celdas.
     */
    private function exportarXlsx(EjecucionInformeRazonado $ejecucion): string
    {
        $ejecucion->load(['definicionInformeRazonado', 'corteReportabilidad', 'metricas', 'excepciones']);

        $spreadsheet = new Spreadsheet;

        $metricasHoja = $spreadsheet->getActiveSheet();
        $metricasHoja->setTitle('Métricas');
        $metricasHoja->fromArray([
            ['Informe', $ejecucion->definicionInformeRazonado->nombre],
            ['Ejecución', $ejecucion->id],
            ['Corte', $ejecucion->corteReportabilidad?->fecha_corte],
            [],
            ['Código', 'Etiqueta', 'Valor', 'Unidad'],
        ]);
        $filaMetrica = 6;
        foreach ($ejecucion->metricas as $metrica) {
            $metricasHoja->fromArray(
                [[$metrica->codigo, $metrica->etiqueta, $metrica->valor, $metrica->unidad]],
                null,
                "A{$filaMetrica}"
            );
            $filaMetrica++;
        }

        $excepcionesHoja = $spreadsheet->createSheet();
        $excepcionesHoja->setTitle('Excepciones');
        $excepcionesHoja->fromArray([['Código', 'Severidad', 'Descripción']]);
        $filaExcepcion = 2;
        foreach ($ejecucion->excepciones as $excepcion) {
            $excepcionesHoja->fromArray(
                [[$excepcion->codigo, $excepcion->severidad, $excepcion->descripcion]],
                null,
                "A{$filaExcepcion}"
            );
            $filaExcepcion++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        $ruta = $this->rutaPara($ejecucion, 'xlsx');
        $writer = new XlsxWriter($spreadsheet);

        $this->guardarDesdeArchivoTemporal(fn (string $tmp) => $writer->save($tmp), $ruta);

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

    /**
     * Extrae el contenido del <body> del HTML renderizado. El parser HTML de
     * PhpWord trabaja mejor con un fragmento sin <head>/<style>.
     */
    private function cuerpoHtml(EjecucionInformeRazonado $ejecucion): string
    {
        $html = $this->render($ejecucion);

        if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $coincidencias) === 1) {
            return $coincidencias[1];
        }

        return $html;
    }

    /**
     * Ejecuta un writer que solo sabe escribir a rutas de filesystem contra un
     * archivo temporal y vuelca el resultado al disco privado, limpiando el
     * temporal incluso ante excepción.
     *
     * @param  callable(string): void  $escribir
     */
    private function guardarDesdeArchivoTemporal(callable $escribir, string $ruta): void
    {
        $temporal = tempnam(sys_get_temp_dir(), 'informe-export-');

        try {
            $escribir($temporal);
            Storage::disk('local')->put($ruta, (string) file_get_contents($temporal));
        } finally {
            @unlink($temporal);
        }
    }

    private function rutaPara(EjecucionInformeRazonado $ejecucion, string $extension): string
    {
        return "informes-razonados/{$ejecucion->id}/informe-{$ejecucion->id}-".now()->format('Ymd-His').".{$extension}";
    }
}
