<?php

namespace App\Services\Adquisiciones;

use App\Models\OrdenCompraMercadoPublico;
use App\Models\OrdenCompraMercadoPublicoItem;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\SolicitudApiExterna;
use App\Services\Integraciones\IntegracionExternaService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class OrdenCompraMercadoPublicoService
{
    public function __construct(private readonly IntegracionExternaService $integracionExterna) {}

    public function buscarLocal(string $codigo): ?OrdenCompraMercadoPublico
    {
        return OrdenCompraMercadoPublico::with(['items', 'proveedor', 'procesoAdquisicion'])
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
    public function compararConApi(OrdenCompraMercadoPublico $oc): array
    {
        $resultado = $this->consultarApiInterno($oc->codigo, $oc);

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
            'diferencias' => $this->calcularDiferencias($oc, $resultado['payload_normalizado']),
            'payload_normalizado' => $resultado['payload_normalizado'],
            'solicitud' => $resultado['solicitud'],
            'snapshot' => $resultado['snapshot'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     */
    public function verificarProveedor(array $payloadNormalizado): ?Proveedor
    {
        $rut = $payloadNormalizado['proveedor']['rut'] ?? null;

        if ($rut === null) {
            return null;
        }

        return Proveedor::where('rutproveedor', Proveedor::normalizarRut($rut))->first();
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     * @return array{orden: OrdenCompraMercadoPublico, proveedor_resultado: string}
     */
    public function guardarDesdeApi(array $payloadNormalizado, SnapshotDatosExterno $snapshot, ?int $procesoAdquisicionId = null, ?int $proveedorIdOverride = null): array
    {
        return DB::transaction(function () use ($payloadNormalizado, $snapshot, $procesoAdquisicionId, $proveedorIdOverride) {
            ['proveedor' => $proveedor, 'resultado' => $proveedorResultado] = $this->resolverProveedor($payloadNormalizado, $proveedorIdOverride);

            $oc = OrdenCompraMercadoPublico::create([
                'codigo' => $payloadNormalizado['codigo'],
                'proveedor_id' => $proveedor->id,
                'proceso_adquisicion_id' => $procesoAdquisicionId,
                'snapshot_datos_externo_id' => $snapshot->id,
                ...$this->camposDelPayload($payloadNormalizado),
            ]);

            $this->crearItems($oc, $payloadNormalizado['items'] ?? []);

            return [
                'orden' => $oc->refresh()->load(['items', 'proveedor', 'procesoAdquisicion']),
                'proveedor_resultado' => $proveedorResultado,
            ];
        });
    }

    /**
     * Resuelve el proveedor emisor de una OC nueva: si se indica un override manual, se usa
     * tal cual; si no, se busca por RUT normalizado y, de no existir, se crea con los datos
     * del payload; si ya existe, se completan únicamente sus campos vacíos.
     *
     * @param  array<string, mixed>  $payloadNormalizado
     * @return array{proveedor: Proveedor, resultado: string}
     */
    private function resolverProveedor(array $payloadNormalizado, ?int $proveedorIdOverride): array
    {
        if ($proveedorIdOverride !== null) {
            return ['proveedor' => Proveedor::findOrFail($proveedorIdOverride), 'resultado' => 'sin_cambios'];
        }

        $proveedor = $this->verificarProveedor($payloadNormalizado);
        $datosProveedor = $payloadNormalizado['proveedor'] ?? [];

        if ($proveedor === null) {
            $proveedor = Proveedor::create([
                'rutproveedor' => $datosProveedor['rut'] ?? '',
                'nombre' => $datosProveedor['nombre'] ?? '',
                'activo' => true,
            ]);

            return ['proveedor' => $proveedor, 'resultado' => 'creado'];
        }

        $camposFaltantes = array_filter([
            'nombre' => $proveedor->nombre === '' ? ($datosProveedor['nombre'] ?? null) : null,
        ], fn ($valor) => $valor !== null && $valor !== '');

        if ($camposFaltantes === []) {
            return ['proveedor' => $proveedor, 'resultado' => 'sin_cambios'];
        }

        $proveedor->fill($camposFaltantes)->save();

        return ['proveedor' => $proveedor, 'resultado' => 'actualizado'];
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     */
    public function aplicarActualizacion(OrdenCompraMercadoPublico $oc, array $payloadNormalizado, SnapshotDatosExterno $snapshot): OrdenCompraMercadoPublico
    {
        return DB::transaction(function () use ($oc, $payloadNormalizado, $snapshot) {
            $oc->update([
                'snapshot_datos_externo_id' => $snapshot->id,
                ...$this->camposDelPayload($payloadNormalizado),
            ]);

            $oc->items()->delete();
            $this->crearItems($oc, $payloadNormalizado['items'] ?? []);

            return $oc->refresh()->load(['items', 'proveedor', 'procesoAdquisicion']);
        });
    }

    /**
     * @return array{encontrada: bool, payload_normalizado: array<string, mixed>|null, solicitud: SolicitudApiExterna, snapshot: SnapshotDatosExterno|null}
     */
    private function consultarApiInterno(string $codigo, ?Model $vinculable): array
    {
        $sistema = SistemaExterno::where('codigo', 'MERCADO_PUBLICO')->firstOrFail();
        $trabajo = $this->integracionExterna->iniciarTrabajo($sistema, 'consulta_orden_compra', 'api');

        $endpoint = rtrim((string) config('services.mercadopublico.base_url'), '/').'/ordenesdecompra.json';
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
        $encontrada = $respuesta->successful() && $this->apiDevuelveOrdenCompra($payloadCrudo, $codigo);

        $solicitud = $this->integracionExterna->registrarSolicitud(
            sistema: $sistema,
            metodoHttp: 'GET',
            endpoint: $endpoint,
            estado: $encontrada ? 'exitosa' : ($respuesta->successful() ? 'no_encontrada' : 'error'),
            payloadEnviado: ['codigo' => $codigo],
            payloadRecibido: $payloadCrudo,
            codigoRespuestaHttp: $respuesta->status(),
            error: $encontrada ? null : 'Orden de compra no encontrada en Mercado Público',
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
     * La API de Órdenes de Compra de Mercado Público envuelve el resultado en
     * `Listado` (posiblemente vacío) cuando la petición es válida, y responde
     * con un cuerpo plano `{"Codigo": <código de error>, "Mensaje": "..."}`
     * (código ajeno al de la OC solicitada) cuando el ticket o los parámetros
     * son inválidos. Por eso SHALL exigirse que `Listado` tenga al menos un
     * elemento y que su `Codigo` coincida exactamente con el solicitado.
     *
     * @param  array<string, mixed>  $payloadCrudo
     */
    private function apiDevuelveOrdenCompra(array $payloadCrudo, string $codigoSolicitado): bool
    {
        $orden = $this->primerElementoListado($payloadCrudo);

        if ($orden === null) {
            return false;
        }

        $codigoRespuesta = $orden['Codigo'] ?? null;

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
     * Traduce el payload crudo de la API de Órdenes de Compra de Mercado Público
     * (la OC vive en `Listado[0]`) a la estructura normalizada que usa este
     * servicio. Mercado Público no expone un plazo de entrega en esta API, por
     * lo que `plazo_entrega_dias` queda siempre nulo.
     *
     * @param  array<string, mixed>  $payloadCrudo
     * @return array<string, mixed>
     */
    private function normalizarPayload(array $payloadCrudo): array
    {
        $orden = $this->primerElementoListado($payloadCrudo) ?? [];

        /** @var array<int, array<string, mixed>> $itemsCrudos */
        $itemsCrudos = (array) ($orden['Items']['Listado'] ?? []);

        $items = collect($itemsCrudos)
            ->map(fn (array $item) => [
                'codigo_producto' => isset($item['CodigoProducto']) ? (string) $item['CodigoProducto'] : null,
                'descripcion' => $item['Producto'] ?? ($item['EspecificacionComprador'] ?? ''),
                'cantidad' => (float) ($item['Cantidad'] ?? 0),
                'precio_unitario' => (float) ($item['PrecioNeto'] ?? 0),
                'monto_total' => (float) ($item['Total'] ?? 0),
            ])
            ->values()
            ->all();

        /** @var array<string, mixed> $fechas */
        $fechas = (array) ($orden['Fechas'] ?? []);

        return [
            'codigo' => $orden['Codigo'] ?? null,
            'estado' => $orden['Estado'] ?? null,
            'moneda' => $orden['TipoMoneda'] ?? 'CLP',
            'forma_pago' => isset($orden['FormaPago']) ? (string) $orden['FormaPago'] : null,
            'plazo_entrega_dias' => null,
            'monto_neto' => isset($orden['TotalNeto']) ? (float) $orden['TotalNeto'] : null,
            'monto_total' => isset($orden['Total']) ? (float) $orden['Total'] : null,
            'fecha_emision' => isset($fechas['FechaEnvio']) ? substr((string) $fechas['FechaEnvio'], 0, 10) : null,
            'organismo_comprador' => [
                'nombre' => $orden['Comprador']['NombreOrganismo'] ?? null,
                'unidad' => $orden['Comprador']['NombreUnidad'] ?? null,
                'rut' => $orden['Comprador']['RutUnidad'] ?? null,
            ],
            'cronograma' => $this->cronogramaDesdeFechas($fechas),
            'proveedor' => [
                'rut' => $orden['Proveedor']['RutSucursal'] ?? null,
                'nombre' => $orden['Proveedor']['Nombre'] ?? null,
            ],
            'items' => $items,
        ];
    }

    /**
     * Construye la línea de tiempo de la OC a partir de los hitos discretos que
     * expone Mercado Público en `Fechas` (no hay un historial de estados como
     * lista propiamente tal en esta API).
     *
     * @param  array<string, mixed>  $fechas
     * @return array<int, array{estado: string, fecha: string}>
     */
    private function cronogramaDesdeFechas(array $fechas): array
    {
        $hitos = [
            'FechaCreacion' => 'Creada',
            'FechaEnvio' => 'Enviada',
            'FechaAceptacion' => 'Aceptada',
            'FechaCancelacion' => 'Cancelada',
        ];

        $cronograma = [];

        foreach ($hitos as $campo => $estado) {
            if (! empty($fechas[$campo] ?? null)) {
                $cronograma[] = ['estado' => $estado, 'fecha' => substr((string) $fechas[$campo], 0, 10)];
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
            'estado_mercado_publico' => $payloadNormalizado['estado'] ?? null,
            'moneda' => $payloadNormalizado['moneda'] ?? null,
            'forma_pago' => $payloadNormalizado['forma_pago'] ?? null,
            'plazo_entrega_dias' => $payloadNormalizado['plazo_entrega_dias'] ?? null,
            'monto_neto' => $payloadNormalizado['monto_neto'] ?? null,
            'monto_total' => $payloadNormalizado['monto_total'] ?? null,
            'fecha_emision' => $payloadNormalizado['fecha_emision'] ?? null,
            'organismo_comprador' => $payloadNormalizado['organismo_comprador'] ?? null,
            'cronograma' => $payloadNormalizado['cronograma'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payloadNormalizado
     * @return array<string, array{local: mixed, api: mixed}>
     */
    private function calcularDiferencias(OrdenCompraMercadoPublico $oc, array $payloadNormalizado): array
    {
        $camposApi = $this->camposDelPayload($payloadNormalizado);
        $camposNumericos = ['plazo_entrega_dias', 'monto_neto', 'monto_total'];
        $diferencias = [];

        foreach ($camposApi as $campo => $valorApi) {
            $valorLocal = $oc->{$campo};

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
    private function crearItems(OrdenCompraMercadoPublico $oc, array $items): void
    {
        foreach ($items as $item) {
            OrdenCompraMercadoPublicoItem::create([
                'orden_compra_mercado_publico_id' => $oc->id,
                'codigo_producto' => $item['codigo_producto'] ?? null,
                'descripcion' => $item['descripcion'] ?? '',
                'cantidad' => $item['cantidad'] ?? 0,
                'precio_unitario' => $item['precio_unitario'] ?? 0,
                'monto_total' => $item['monto_total'] ?? 0,
            ]);
        }
    }
}
