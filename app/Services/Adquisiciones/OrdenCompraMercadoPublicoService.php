<?php

namespace App\Services\Adquisiciones;

use App\Exceptions\OrdenCompraSinProveedorException;
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
    private const URL_BASE_DETALLE_OC_MERCADO_PUBLICO = 'https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/';

    public function __construct(private readonly IntegracionExternaService $integracionExterna) {}

    public function buscarLocal(string $codigo): ?OrdenCompraMercadoPublico
    {
        return OrdenCompraMercadoPublico::with(['items', 'proveedor', 'procesoAdquisicion', 'snapshot'])
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
     * Resuelve el enlace real de descarga del PDF de una OC, scrapeando la
     * página pública de detalle de Mercado Público (no hay campo de enlace
     * en la API JSON). El token del botón nativo de descarga es estable por
     * OC, así que se arma una URL propia a partir de él en vez de redirigir
     * a una URL tomada literalmente de la respuesta externa.
     */
    public function resolverUrlPdf(string $codigo): ?string
    {
        $sistema = SistemaExterno::where('codigo', 'MERCADO_PUBLICO')->firstOrFail();
        $trabajo = $this->integracionExterna->iniciarTrabajo($sistema, 'resolver_pdf_orden_compra', 'scraping');

        $endpoint = self::URL_BASE_DETALLE_OC_MERCADO_PUBLICO.'DetailsPurchaseOrder.aspx?codigoOC='.urlencode($codigo);
        $inicio = microtime(true);

        try {
            $respuesta = Http::get($endpoint);
        } catch (Throwable $e) {
            $this->integracionExterna->registrarSolicitud(
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

            return null;
        }

        $duracionMs = (int) ((microtime(true) - $inicio) * 1000);
        $urlPdf = $respuesta->successful() ? $this->extraerUrlPdf($respuesta->body()) : null;

        $this->integracionExterna->registrarSolicitud(
            sistema: $sistema,
            metodoHttp: 'GET',
            endpoint: $endpoint,
            estado: $urlPdf !== null ? 'exitosa' : 'no_encontrada',
            payloadEnviado: ['codigo' => $codigo],
            codigoRespuestaHttp: $respuesta->status(),
            error: $urlPdf === null ? 'No se encontró el enlace de descarga de PDF en la página de Mercado Público' : null,
            duracionMs: $duracionMs,
            trabajo: $trabajo,
        );

        $this->integracionExterna->finalizarTrabajo($trabajo, $urlPdf !== null ? 'completado' : 'error');

        return $urlPdf;
    }

    /**
     * El botón nativo de descarga (`#imgPDF`) arma su enlace con
     * `onclick="open(&#39;PDFReport.aspx?qs=<token>&#39;,...)"`. Solo se
     * acepta el patrón exacto con un token de caracteres base64, y la URL
     * final se arma con la constante base propia — nunca se redirige a una
     * URL construida a partir de contenido arbitrario de la respuesta.
     */
    private function extraerUrlPdf(string $html): ?string
    {
        if (! preg_match('/id="imgPDF"[^>]*onclick="open\(&#39;(PDFReport\.aspx\?qs=[A-Za-z0-9+\/=]+)&#39;/', $html, $coincidencia)) {
            return null;
        }

        return self::URL_BASE_DETALLE_OC_MERCADO_PUBLICO.$coincidencia[1];
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
     * Columnas del catálogo de proveedores que se pueblan/completan con los
     * datos del payload de Mercado Público (además de `rutproveedor`, que es la
     * identidad, y `activo`). La clave del payload normalizado coincide con la
     * columna del modelo.
     *
     * @var list<string>
     */
    private const CAMPOS_COMPLETABLES_PROVEEDOR = [
        'nombre',
        'direccion',
        'comuna',
        'region',
        'giro',
        'correo',
        'contacto',
        'contacto_cargo',
        'contacto_telefono',
    ];

    /**
     * Resuelve el proveedor emisor de una OC nueva: si se indica un override manual, se usa
     * tal cual; si no, se exige un RUT identificable y se busca por RUT normalizado; de no
     * existir se crea con todos los datos disponibles del payload, y si ya existe se completan
     * únicamente sus campos vacíos sin sobrescribir los ya cargados.
     *
     * @param  array<string, mixed>  $payloadNormalizado
     * @return array{proveedor: Proveedor, resultado: string}
     *
     * @throws OrdenCompraSinProveedorException cuando el payload no aporta un RUT identificable
     */
    private function resolverProveedor(array $payloadNormalizado, ?int $proveedorIdOverride): array
    {
        if ($proveedorIdOverride !== null) {
            return ['proveedor' => Proveedor::findOrFail($proveedorIdOverride), 'resultado' => 'sin_cambios'];
        }

        /** @var array<string, mixed> $datosProveedor */
        $datosProveedor = $payloadNormalizado['proveedor'] ?? [];

        if (Proveedor::normalizarRut((string) ($datosProveedor['rut'] ?? '')) === '') {
            throw new OrdenCompraSinProveedorException;
        }

        $proveedor = $this->verificarProveedor($payloadNormalizado);
        $camposPayload = $this->camposCompletablesProveedor($datosProveedor);

        if ($proveedor === null) {
            $proveedor = Proveedor::create([
                'rutproveedor' => $datosProveedor['rut'],
                'nombre' => '',
                'activo' => true,
                ...$camposPayload,
            ]);

            return ['proveedor' => $proveedor, 'resultado' => 'creado'];
        }

        $camposFaltantes = [];

        foreach ($camposPayload as $columna => $valor) {
            $actual = $proveedor->{$columna};

            if ($actual === null || $actual === '') {
                $camposFaltantes[$columna] = $valor;
            }
        }

        if ($camposFaltantes === []) {
            return ['proveedor' => $proveedor, 'resultado' => 'sin_cambios'];
        }

        $proveedor->fill($camposFaltantes)->save();

        return ['proveedor' => $proveedor, 'resultado' => 'actualizado'];
    }

    /**
     * Extrae del payload del proveedor solo los campos completables que traen un valor
     * no vacío, mapeados a sus columnas del modelo `Proveedor`.
     *
     * @param  array<string, mixed>  $datosProveedor
     * @return array<string, string>
     */
    private function camposCompletablesProveedor(array $datosProveedor): array
    {
        $campos = [];

        foreach (self::CAMPOS_COMPLETABLES_PROVEEDOR as $columna) {
            $valor = $this->trimONull($datosProveedor[$columna] ?? null);

            if ($valor !== null) {
                $campos[$columna] = $valor;
            }
        }

        return $campos;
    }

    /**
     * Trimea un valor y lo deja en `null` si viene ausente o queda vacío tras el trim
     * (Mercado Público entrega strings vacíos o con solo espacios en varios campos).
     */
    private function trimONull(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
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
                'nombre' => $this->trimONull($orden['Proveedor']['Nombre'] ?? null),
                'direccion' => $this->trimONull($orden['Proveedor']['Direccion'] ?? null),
                'comuna' => $this->trimONull($orden['Proveedor']['Comuna'] ?? null),
                'region' => $this->trimONull($orden['Proveedor']['Region'] ?? null),
                'giro' => $this->trimONull($orden['Proveedor']['Actividad'] ?? null),
                'correo' => $this->trimONull($orden['Proveedor']['MailContacto'] ?? null),
                'contacto' => $this->trimONull($orden['Proveedor']['NombreContacto'] ?? null),
                'contacto_cargo' => $this->trimONull($orden['Proveedor']['CargoContacto'] ?? null),
                'contacto_telefono' => $this->trimONull($orden['Proveedor']['FonoContacto'] ?? null),
            ],
            'items' => $items,
        ];
    }

    /**
     * Construye la línea de tiempo de la OC a partir de los hitos discretos que
     * expone Mercado Público en `Fechas` (no hay un historial de estados como
     * lista propiamente tal en esta API). Se conserva la fecha y hora tal como
     * las entrega la API, sin truncarlas a solo el día.
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
                $cronograma[] = ['estado' => $estado, 'fecha' => (string) $fechas[$campo]];
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
