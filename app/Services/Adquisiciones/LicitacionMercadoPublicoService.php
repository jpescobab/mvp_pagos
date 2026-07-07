<?php

namespace App\Services\Adquisiciones;

use App\Models\LicitacionMercadoPublico;
use App\Models\LicitacionMercadoPublicoItem;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\SolicitudApiExterna;
use App\Services\Integraciones\IntegracionExternaService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class LicitacionMercadoPublicoService
{
    public function __construct(private readonly IntegracionExternaService $integracionExterna) {}

    public function buscarLocal(string $codigo): ?LicitacionMercadoPublico
    {
        return LicitacionMercadoPublico::with(['items', 'procesoAdquisicion', 'snapshot'])
            ->where('codigo', $codigo)
            ->first();
    }

    /**
     * @return array{encontrada: bool, payload_normalizado: array<string, mixed>|null, solicitud: SolicitudApiExterna, snapshot: SnapshotDatosExterno|null}
     */
    public function consultarApi(string $codigo): array
    {
        return $this->consultarApiInterno($codigo, null);
    }

    /**
     * @return array{encontrada: bool, diferencias: array<string, array{local: mixed, api: mixed}>, payload_normalizado: array<string, mixed>|null, solicitud: SolicitudApiExterna, snapshot: SnapshotDatosExterno|null}
     */
    public function compararConApi(LicitacionMercadoPublico $licitacion): array
    {
        $resultado = $this->consultarApiInterno($licitacion->codigo, $licitacion);

        if (! $resultado['encontrada']) {
            return [
                'encontrada' => false,
                'diferencias' => [],
                'payload_normalizado' => null,
                'solicitud' => $resultado['solicitud'],
                'snapshot' => null,
            ];
        }

        return [
            'encontrada' => true,
            'diferencias' => $this->calcularDiferencias($licitacion, $resultado['payload_normalizado']),
            'payload_normalizado' => $resultado['payload_normalizado'],
            'solicitud' => $resultado['solicitud'],
            'snapshot' => $resultado['snapshot'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     * @return array{licitacion: LicitacionMercadoPublico}
     */
    public function guardarDesdeApi(array $payloadNormalizado, SnapshotDatosExterno $snapshot, ?int $procesoAdquisicionId = null): array
    {
        return DB::transaction(function () use ($payloadNormalizado, $snapshot, $procesoAdquisicionId) {
            $licitacion = LicitacionMercadoPublico::create([
                'codigo' => $payloadNormalizado['codigo'],
                'proceso_adquisicion_id' => $procesoAdquisicionId,
                'snapshot_datos_externo_id' => $snapshot->id,
                ...$this->camposDelPayload($payloadNormalizado),
            ]);

            $this->crearItems($licitacion, $payloadNormalizado['items'] ?? []);

            return ['licitacion' => $licitacion->refresh()->load(['items', 'procesoAdquisicion'])];
        });
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     */
    public function aplicarActualizacion(LicitacionMercadoPublico $licitacion, array $payloadNormalizado, SnapshotDatosExterno $snapshot): LicitacionMercadoPublico
    {
        return DB::transaction(function () use ($licitacion, $payloadNormalizado, $snapshot) {
            $licitacion->update([
                'snapshot_datos_externo_id' => $snapshot->id,
                ...$this->camposDelPayload($payloadNormalizado),
            ]);

            $licitacion->items()->delete();
            $this->crearItems($licitacion, $payloadNormalizado['items'] ?? []);

            return $licitacion->refresh()->load(['items', 'procesoAdquisicion']);
        });
    }

    /**
     * @return array{encontrada: bool, payload_normalizado: array<string, mixed>|null, solicitud: SolicitudApiExterna, snapshot: SnapshotDatosExterno|null}
     */
    private function consultarApiInterno(string $codigo, ?Model $vinculable): array
    {
        $sistema = SistemaExterno::where('codigo', 'MERCADO_PUBLICO')->firstOrFail();
        $trabajo = $this->integracionExterna->iniciarTrabajo($sistema, 'consulta_licitacion', 'api');

        $endpoint = rtrim((string) config('services.mercadopublico.base_url'), '/').'/licitaciones.json';
        $inicio = microtime(true);

        try {
            $respuesta = Http::get($endpoint, [
                'codigo' => $codigo,
                'ticket' => config('services.mercadopublico.api_key'),
            ]);
        } catch (Throwable $e) {
            $solicitud = $this->integracionExterna->registrarSolicitud(
                sistema: $sistema,
                metodoHttp: 'GET',
                endpoint: $endpoint,
                estado: 'error',
                payloadEnviado: ['codigo' => $codigo],
                error: $e->getMessage(),
                duracionMs: (int) ((microtime(true) - $inicio) * 1000),
                trabajo: $trabajo,
            );

            $this->integracionExterna->finalizarTrabajo($trabajo, 'error', $e->getMessage());

            return ['encontrada' => false, 'payload_normalizado' => null, 'solicitud' => $solicitud, 'snapshot' => null];
        }

        $duracionMs = (int) ((microtime(true) - $inicio) * 1000);
        $payloadCrudo = $respuesta->json() ?? [];
        $encontrada = $respuesta->successful() && $this->apiDevuelveLicitacion($payloadCrudo, $codigo);

        $solicitud = $this->integracionExterna->registrarSolicitud(
            sistema: $sistema,
            metodoHttp: 'GET',
            endpoint: $endpoint,
            estado: $encontrada ? 'exitosa' : ($respuesta->successful() ? 'no_encontrada' : 'error'),
            payloadEnviado: ['codigo' => $codigo],
            payloadRecibido: $payloadCrudo,
            codigoRespuestaHttp: $respuesta->status(),
            error: $encontrada ? null : 'Licitación no encontrada en Mercado Público',
            duracionMs: $duracionMs,
            trabajo: $trabajo,
        );

        if (! $encontrada) {
            $this->integracionExterna->finalizarTrabajo($trabajo, 'completado');

            return ['encontrada' => false, 'payload_normalizado' => null, 'solicitud' => $solicitud, 'snapshot' => null];
        }

        $payloadNormalizado = $this->normalizarPayload($payloadCrudo);

        $snapshot = $this->integracionExterna->registrarSnapshot(
            sistema: $sistema,
            metodoCaptura: 'api',
            payloadCrudo: $payloadCrudo,
            payloadNormalizado: $payloadNormalizado,
            referenciaExterna: $codigo,
            trabajo: $trabajo,
            solicitud: $solicitud,
            vinculable: $vinculable,
        );

        $this->integracionExterna->finalizarTrabajo($trabajo, 'completado');

        return ['encontrada' => true, 'payload_normalizado' => $payloadNormalizado, 'solicitud' => $solicitud, 'snapshot' => $snapshot];
    }

    /**
     * La API de Licitaciones de Mercado Público envuelve el resultado en
     * `Listado` (posiblemente vacío) cuando la petición es válida, y responde
     * con un cuerpo plano `{"Codigo": <código de error>, "Mensaje": "..."}`
     * (ajeno al código de la licitación solicitada) cuando el ticket o los
     * parámetros son inválidos. Por eso SHALL exigirse que `Listado` tenga al
     * menos un elemento y que su `CodigoExterno` coincida exactamente con el
     * solicitado.
     *
     * @param  array<string, mixed>  $payloadCrudo
     */
    private function apiDevuelveLicitacion(array $payloadCrudo, string $codigoSolicitado): bool
    {
        $licitacion = $this->primerElementoListado($payloadCrudo);

        if ($licitacion === null) {
            return false;
        }

        $codigoRespuesta = $licitacion['CodigoExterno'] ?? null;

        if ($codigoRespuesta === null) {
            return false;
        }

        return strcasecmp((string) $codigoRespuesta, $codigoSolicitado) === 0;
    }

    /**
     * @param  array<string, mixed>  $payloadCrudo
     * @return array<string, mixed>|null
     */
    private function primerElementoListado(array $payloadCrudo): ?array
    {
        $listado = $payloadCrudo['Listado'] ?? null;

        if (! is_array($listado) || $listado === [] || ! is_array($listado[0] ?? null)) {
            return null;
        }

        return $listado[0];
    }

    /**
     * Traduce el payload crudo de la API de Licitaciones de Mercado Público
     * (la licitación vive en `Listado[0]`) a la estructura normalizada que usa
     * este servicio. A diferencia de una OC, una licitación no tiene un único
     * proveedor emisor: la adjudicación (si existe) vive a nivel de licitación
     * (`Adjudicacion`) y, por separado, a nivel de cada ítem
     * (`Items.Listado[].Adjudicacion`), pudiendo haber un proveedor distinto
     * adjudicado por ítem.
     *
     * @param  array<string, mixed>  $payloadCrudo
     * @return array<string, mixed>
     */
    private function normalizarPayload(array $payloadCrudo): array
    {
        $licitacion = $this->primerElementoListado($payloadCrudo) ?? [];

        /** @var array<int, array<string, mixed>> $itemsCrudos */
        $itemsCrudos = (array) ($licitacion['Items']['Listado'] ?? []);

        $items = collect($itemsCrudos)
            ->map(fn (array $item) => [
                'correlativo' => isset($item['Correlativo']) ? (int) $item['Correlativo'] : null,
                'codigo_producto' => isset($item['CodigoProducto']) ? (string) $item['CodigoProducto'] : null,
                'categoria' => $item['Categoria'] ?? null,
                'nombre_producto' => $item['NombreProducto'] ?? null,
                'descripcion' => $item['Descripcion'] ?? '',
                'unidad_medida' => $item['UnidadMedida'] ?? null,
                'cantidad' => (float) ($item['Cantidad'] ?? 0),
                'adjudicacion' => $this->adjudicacionDelItem($item['Adjudicacion'] ?? null),
            ])
            ->values()
            ->all();

        /** @var array<string, mixed> $fechas */
        $fechas = (array) ($licitacion['Fechas'] ?? []);

        return [
            'codigo' => $licitacion['CodigoExterno'] ?? null,
            'nombre' => $licitacion['Nombre'] ?? null,
            'estado' => $licitacion['Estado'] ?? null,
            'codigo_estado' => isset($licitacion['CodigoEstado']) ? (int) $licitacion['CodigoEstado'] : null,
            'moneda' => $licitacion['Moneda'] ?? 'CLP',
            'monto_estimado' => isset($licitacion['MontoEstimado']) ? (float) $licitacion['MontoEstimado'] : null,
            'organismo_comprador' => [
                'nombre' => $licitacion['Comprador']['NombreOrganismo'] ?? null,
                'unidad' => $licitacion['Comprador']['NombreUnidad'] ?? null,
                'rut' => $licitacion['Comprador']['RutUnidad'] ?? null,
            ],
            'cronograma' => $this->cronogramaDesdeFechas($fechas),
            'adjudicacion' => $this->adjudicacionDeLicitacion($licitacion['Adjudicacion'] ?? null),
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $adjudicacion
     * @return array<string, mixed>|null
     */
    private function adjudicacionDeLicitacion(?array $adjudicacion): ?array
    {
        if ($adjudicacion === null) {
            return null;
        }

        return [
            'tipo' => $adjudicacion['Tipo'] ?? null,
            'fecha' => $adjudicacion['Fecha'] ?? null,
            'numero' => $adjudicacion['Numero'] ?? null,
            'numero_oferentes' => isset($adjudicacion['NumeroOferentes']) ? (int) $adjudicacion['NumeroOferentes'] : null,
            'url_acta' => $adjudicacion['UrlActa'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $adjudicacion
     * @return array<string, mixed>|null
     */
    private function adjudicacionDelItem(?array $adjudicacion): ?array
    {
        if ($adjudicacion === null) {
            return null;
        }

        return [
            'rut_proveedor' => $adjudicacion['RutProveedor'] ?? null,
            'nombre_proveedor' => $adjudicacion['NombreProveedor'] ?? null,
            'cantidad' => isset($adjudicacion['Cantidad']) ? (float) $adjudicacion['Cantidad'] : null,
            'monto_unitario' => isset($adjudicacion['MontoUnitario']) ? (float) $adjudicacion['MontoUnitario'] : null,
        ];
    }

    /**
     * Construye la línea de tiempo de la licitación a partir de los hitos
     * discretos que expone Mercado Público en `Fechas`. Se conserva la fecha y
     * hora tal como las entrega la API, sin truncarlas a solo el día, y se
     * omite cualquier hito no informado.
     *
     * @param  array<string, mixed>  $fechas
     * @return array<int, array{estado: string, fecha: string}>
     */
    private function cronogramaDesdeFechas(array $fechas): array
    {
        $hitos = [
            'FechaCreacion' => 'Creada',
            'FechaPublicacion' => 'Publicada',
            'FechaInicio' => 'Inicio de preguntas',
            'FechaFinal' => 'Cierre de preguntas',
            'FechaPubRespuestas' => 'Publicación de respuestas',
            'FechaCierre' => 'Cierre de recepción de ofertas',
            'FechaActoAperturaTecnica' => 'Apertura técnica',
            'FechaActoAperturaEconomica' => 'Apertura económica',
            'FechaAdjudicacion' => 'Adjudicación',
        ];

        $cronograma = [];

        foreach ($hitos as $campo => $estado) {
            $valor = $fechas[$campo] ?? ($campo === 'FechaAdjudicacion' ? ($fechas['FechaEstimadaAdjudicacion'] ?? null) : null);

            if (! empty($valor)) {
                $cronograma[] = ['estado' => $estado, 'fecha' => (string) $valor];
            }
        }

        return $cronograma;
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     * @return array<string, mixed>
     */
    private function camposDelPayload(array $payloadNormalizado): array
    {
        return [
            'nombre' => $payloadNormalizado['nombre'] ?? null,
            'estado_mercado_publico' => $payloadNormalizado['estado'] ?? null,
            'codigo_estado_mercado_publico' => $payloadNormalizado['codigo_estado'] ?? null,
            'moneda' => $payloadNormalizado['moneda'] ?? null,
            'monto_estimado' => $payloadNormalizado['monto_estimado'] ?? null,
            'organismo_comprador' => $payloadNormalizado['organismo_comprador'] ?? null,
            'cronograma' => $payloadNormalizado['cronograma'] ?? null,
            'adjudicacion' => $payloadNormalizado['adjudicacion'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     * @return array<string, array{local: mixed, api: mixed}>
     */
    private function calcularDiferencias(LicitacionMercadoPublico $licitacion, array $payloadNormalizado): array
    {
        $camposApi = $this->camposDelPayload($payloadNormalizado);
        $camposNumericos = ['monto_estimado'];
        $diferencias = [];

        foreach ($camposApi as $campo => $valorApi) {
            $valorLocal = $licitacion->{$campo};

            if ($valorLocal instanceof CarbonInterface) {
                $valorLocal = $valorLocal->format('Y-m-d');
            }

            if (in_array($campo, $camposNumericos, true)) {
                $difiere = $this->numerosDifieren($valorLocal, $valorApi);
            } elseif (is_array($valorApi) || is_array($valorLocal)) {
                $difiere = $valorLocal !== $valorApi;
            } else {
                $difiere = (string) $valorLocal !== (string) $valorApi;
            }

            if ($difiere) {
                $diferencias[$campo] = ['local' => $valorLocal, 'api' => $valorApi];
            }
        }

        return $diferencias;
    }

    private function numerosDifieren(mixed $valorLocal, mixed $valorApi): bool
    {
        if ($valorLocal === null || $valorApi === null) {
            return $valorLocal !== $valorApi;
        }

        return abs((float) $valorLocal - (float) $valorApi) > 0.001;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function crearItems(LicitacionMercadoPublico $licitacion, array $items): void
    {
        foreach ($items as $item) {
            LicitacionMercadoPublicoItem::create([
                'licitacion_mercado_publico_id' => $licitacion->id,
                'correlativo' => $item['correlativo'] ?? null,
                'codigo_producto' => $item['codigo_producto'] ?? null,
                'categoria' => $item['categoria'] ?? null,
                'nombre_producto' => $item['nombre_producto'] ?? null,
                'descripcion' => $item['descripcion'] ?? '',
                'unidad_medida' => $item['unidad_medida'] ?? null,
                'cantidad' => $item['cantidad'] ?? 0,
                'adjudicacion' => $item['adjudicacion'] ?? null,
            ]);
        }
    }
}
