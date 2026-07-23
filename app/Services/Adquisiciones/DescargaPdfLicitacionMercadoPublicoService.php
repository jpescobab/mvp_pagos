<?php

namespace App\Services\Adquisiciones;

use App\Models\LicitacionMercadoPublico;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\SolicitudApiExterna;
use App\Models\TrabajoIntegracion;
use App\Services\Integraciones\IntegracionExternaService;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Obtiene el PDF de la ficha de una Licitación desde la página pública de
 * Mercado Público y lo conserva como evidencia.
 *
 * A diferencia de la Orden de Compra —cuyo botón de descarga expone un enlace
 * `GET` estable que se extrae con una expresión regular y al que basta
 * redirigir—, la ficha de Licitación no publica ninguna URL de PDF: el archivo
 * solo se materializa como respuesta a un postback de ASP.NET WebForms. Por eso
 * acá el PDF llega al servidor y no al navegador del usuario, y por eso se
 * persiste con su snapshot en vez de redirigir.
 */
class DescargaPdfLicitacionMercadoPublicoService
{
    private const URL_BASE_DETALLE_LICITACION_MERCADO_PUBLICO = 'https://www.mercadopublico.cl/Procurement/Modules/RFB/DetailsAcquisition.aspx';

    private const CODIGO_SISTEMA_EXTERNO = 'MERCADO_PUBLICO';

    private const METODO_CAPTURA = 'scraping_pdf';

    private const DIRECTORIO = 'mercado-publico-pdf';

    /**
     * Control de la ficha pública que dispara la generación del PDF.
     */
    private const CONTROL_DESCARGA = 'descargar_pdf';

    public function __construct(private readonly IntegracionExternaService $integracionExterna) {}

    /**
     * @return array{contenido: string, nombre_archivo: string}|null `null` cuando no fue posible obtenerlo.
     */
    public function obtener(string $codigo): ?array
    {
        $conservado = $this->buscarConservado($codigo);

        if ($conservado !== null) {
            return $conservado;
        }

        return $this->capturar($codigo);
    }

    /**
     * Reutiliza la captura previa del mismo código. Un snapshot cuyo archivo ya
     * no está en disco no se considera utilizable: se vuelve a capturar en vez
     * de fallar.
     *
     * @return array{contenido: string, nombre_archivo: string}|null
     */
    private function buscarConservado(string $codigo): ?array
    {
        $sistema = $this->sistemaExterno();

        $snapshot = SnapshotDatosExterno::query()
            ->where('sistema_externo_id', $sistema->id)
            ->where('referencia_externa', $codigo)
            ->where('metodo_captura', self::METODO_CAPTURA)
            ->orderByDesc('id')
            ->first();

        if ($snapshot === null) {
            return null;
        }

        $ruta = $snapshot->payload_crudo['ruta_archivo'] ?? null;

        if (! is_string($ruta) || ! Storage::disk('local')->exists($ruta)) {
            return null;
        }

        $contenido = Storage::disk('local')->get($ruta);

        if ($contenido === null) {
            return null;
        }

        return [
            'contenido' => $contenido,
            'nombre_archivo' => $this->nombreArchivo($codigo),
        ];
    }

    /**
     * @return array{contenido: string, nombre_archivo: string}|null
     */
    private function capturar(string $codigo): ?array
    {
        $sistema = $this->sistemaExterno();
        $trabajo = $this->integracionExterna->iniciarTrabajo($sistema, 'descargar_pdf_licitacion', 'scraping');

        $endpointFicha = self::URL_BASE_DETALLE_LICITACION_MERCADO_PUBLICO.'?idlicitacion='.urlencode($codigo);
        $cookies = new CookieJar;
        $inicio = microtime(true);

        try {
            $respuestaFicha = Http::withOptions(['cookies' => $cookies])->get($endpointFicha);
        } catch (Throwable $e) {
            $this->integracionExterna->registrarSolicitud(
                sistema: $sistema,
                metodoHttp: 'GET',
                endpoint: $endpointFicha,
                estado: 'error',
                payloadEnviado: ['codigo' => $codigo],
                error: $e->getMessage(),
                duracionMs: (int) ((microtime(true) - $inicio) * 1000),
                trabajo: $trabajo,
            );

            $this->integracionExterna->finalizarTrabajo($trabajo, 'error', $e->getMessage());

            return null;
        }

        $duracionFicha = (int) ((microtime(true) - $inicio) * 1000);
        $html = $respuestaFicha->successful() ? $respuestaFicha->body() : '';
        $estadoFormulario = $html !== '' ? $this->extraerEstadoFormulario($html) : null;

        $this->integracionExterna->registrarSolicitud(
            sistema: $sistema,
            metodoHttp: 'GET',
            endpoint: $endpointFicha,
            estado: $estadoFormulario !== null ? 'exitosa' : 'no_encontrada',
            payloadEnviado: ['codigo' => $codigo],
            codigoRespuestaHttp: $respuestaFicha->status(),
            error: $estadoFormulario !== null ? null : 'La ficha pública de Mercado Público no ofrece la descarga de PDF para este código',
            duracionMs: $duracionFicha,
            trabajo: $trabajo,
        );

        if ($estadoFormulario === null) {
            $this->integracionExterna->finalizarTrabajo($trabajo, 'error', 'Ficha sin acción de descarga de PDF');

            return null;
        }

        // El postback va contra la URL final del redirect (`?qs=<token>`), no
        // contra la original: la de `?idlicitacion=` responde otro 302 y pierde
        // el estado del formulario recién cosechado.
        $endpointPostback = $respuestaFicha->effectiveUri()?->__toString() ?? $endpointFicha;
        $campos = $estadoFormulario + ['__EVENTTARGET' => self::CONTROL_DESCARGA, '__EVENTARGUMENT' => ''];
        $inicioPdf = microtime(true);

        try {
            $respuestaPdf = Http::withOptions(['cookies' => $cookies])
                ->asForm()
                ->withHeaders(['Referer' => $endpointPostback])
                ->post($endpointPostback, $campos);
        } catch (Throwable $e) {
            $this->integracionExterna->registrarSolicitud(
                sistema: $sistema,
                metodoHttp: 'POST',
                endpoint: $endpointPostback,
                estado: 'error',
                payloadEnviado: ['codigo' => $codigo, 'evento' => self::CONTROL_DESCARGA],
                error: $e->getMessage(),
                duracionMs: (int) ((microtime(true) - $inicioPdf) * 1000),
                trabajo: $trabajo,
            );

            $this->integracionExterna->finalizarTrabajo($trabajo, 'error', $e->getMessage());

            return null;
        }

        $duracionPdf = (int) ((microtime(true) - $inicioPdf) * 1000);
        $contenido = $respuestaPdf->body();
        $esPdf = $respuestaPdf->successful()
            && str_contains((string) $respuestaPdf->header('Content-Type'), 'application/pdf')
            && str_starts_with($contenido, '%PDF-');

        $solicitudPdf = $this->integracionExterna->registrarSolicitud(
            sistema: $sistema,
            metodoHttp: 'POST',
            endpoint: $endpointPostback,
            estado: $esPdf ? 'exitosa' : 'no_encontrada',
            payloadEnviado: ['codigo' => $codigo, 'evento' => self::CONTROL_DESCARGA],
            codigoRespuestaHttp: $respuestaPdf->status(),
            error: $esPdf ? null : 'Mercado Público no respondió con un PDF a la solicitud de descarga',
            duracionMs: $duracionPdf,
            trabajo: $trabajo,
        );

        if (! $esPdf) {
            $this->integracionExterna->finalizarTrabajo($trabajo, 'error', 'La respuesta de descarga no es un PDF');

            return null;
        }

        $this->conservar(
            $codigo,
            $contenido,
            $endpointFicha,
            $respuestaPdf->header('Content-Type'),
            $sistema,
            $trabajo,
            $solicitudPdf,
        );

        $this->integracionExterna->finalizarTrabajo($trabajo, 'completado');

        return ['contenido' => $contenido, 'nombre_archivo' => $this->nombreArchivo($codigo)];
    }

    /**
     * Persiste el PDF en el disco privado y registra su snapshot.
     *
     * El hash del ARCHIVO va dentro del payload como `hash_pdf`: la columna
     * `hash` del snapshot la calcula `registrarSnapshot()` sobre el JSON del
     * payload, no sobre el binario, así que sin este campo el snapshot no
     * probaría nada sobre el documento entregado.
     */
    private function conservar(
        string $codigo,
        string $contenido,
        string $urlFicha,
        ?string $contentType,
        SistemaExterno $sistema,
        TrabajoIntegracion $trabajo,
        SolicitudApiExterna $solicitud,
    ): void {
        $ruta = self::DIRECTORIO.'/'.$this->nombreArchivo($codigo);
        Storage::disk('local')->put($ruta, $contenido);

        $this->integracionExterna->registrarSnapshot(
            sistema: $sistema,
            metodoCaptura: self::METODO_CAPTURA,
            payloadCrudo: [
                'codigo' => $codigo,
                'url_ficha' => $urlFicha,
                'nombre_archivo' => $this->nombreArchivo($codigo),
                'content_type' => $contentType,
                'tamano_bytes' => strlen($contenido),
                'ruta_archivo' => $ruta,
                'hash_pdf' => hash('sha256', $contenido),
            ],
            referenciaExterna: $codigo,
            trabajo: $trabajo,
            solicitud: $solicitud,
            vinculable: LicitacionMercadoPublico::where('codigo', $codigo)->first(),
        );
    }

    /**
     * Estado mínimo del formulario WebForms necesario para el postback. Esta
     * página no emite `__EVENTVALIDATION`; se incluye solo si aparece, para no
     * romper si Mercado Público lo agrega.
     *
     * @return array<string, string>|null `null` si la ficha no ofrece la descarga.
     */
    private function extraerEstadoFormulario(string $html): ?array
    {
        if (! str_contains($html, self::CONTROL_DESCARGA)) {
            return null;
        }

        $campos = [];

        foreach (['__VIEWSTATE', '__VIEWSTATEGENERATOR', '__EVENTVALIDATION'] as $campo) {
            if (preg_match('/id="'.$campo.'"[^>]*value="([^"]*)"/', $html, $coincidencia) === 1) {
                $campos[$campo] = html_entity_decode($coincidencia[1], ENT_QUOTES | ENT_HTML5);
            }
        }

        if (! isset($campos['__VIEWSTATE'])) {
            return null;
        }

        return $campos;
    }

    private function nombreArchivo(string $codigo): string
    {
        return 'PDF'.preg_replace('/[^A-Za-z0-9\-_]/', '_', $codigo).'.pdf';
    }

    private function sistemaExterno(): SistemaExterno
    {
        return SistemaExterno::where('codigo', self::CODIGO_SISTEMA_EXTERNO)->firstOrFail();
    }
}
