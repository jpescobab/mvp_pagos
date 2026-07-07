<?php

use App\Models\OrdenCompraMercadoPublico;
use App\Models\OrdenCompraMercadoPublicoItem;
use App\Models\Proveedor;
use App\Models\SnapshotDatosExterno;
use App\Models\SolicitudApiExterna;
use App\Services\Adquisiciones\OrdenCompraMercadoPublicoService;
use Database\Seeders\IntegracionesSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * @return array<string, mixed>
 */
function ordenCrudaMercadoPublico(string $codigo, array $overrides = []): array
{
    return array_merge([
        'Codigo' => $codigo,
        'Estado' => 'Aceptada',
        'TipoMoneda' => 'CLP',
        'FormaPago' => '2',
        'TotalNeto' => 100000,
        'Total' => 119000,
        'Fechas' => [
            'FechaEnvio' => '2026-04-20 09:15:00',
            'FechaAceptacion' => '2026-05-01 14:30:00',
        ],
        'Comprador' => [
            'NombreOrganismo' => 'Corporación Administrativa del Poder Judicial',
            'NombreUnidad' => 'Corte de Apelaciones',
            'RutUnidad' => '60.503.000-9',
        ],
        'Proveedor' => [
            'RutSucursal' => '76.123.456-7',
            'Nombre' => 'Proveedor de Prueba SpA',
        ],
        'Items' => [
            'Listado' => [
                ['CodigoProducto' => 'A-1', 'Producto' => 'Resma de papel', 'Cantidad' => 10, 'PrecioNeto' => 5000, 'Total' => 50000],
                ['CodigoProducto' => 'A-2', 'Producto' => 'Notebook', 'Cantidad' => 1, 'PrecioNeto' => 69000, 'Total' => 69000],
            ],
        ],
    ], $overrides);
}

/**
 * Envuelve la orden en la forma real de la respuesta de Mercado Público:
 * `{"Cantidad": 1, "Listado": [{...}]}`.
 *
 * @return array<string, mixed>
 */
function payloadCrudoOcMercadoPublico(string $codigo, array $overrides = []): array
{
    return [
        'Cantidad' => 1,
        'Version' => 'v1',
        'Listado' => [ordenCrudaMercadoPublico($codigo, $overrides)],
    ];
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->servicio = app(OrdenCompraMercadoPublicoService::class);
});

test('buscarLocal encuentra una OC existente por código y retorna null si no existe', function () {
    OrdenCompraMercadoPublico::factory()->create(['codigo' => 'OC-LOCAL-001']);

    expect($this->servicio->buscarLocal('OC-LOCAL-001'))->not->toBeNull();
    expect($this->servicio->buscarLocal('OC-INEXISTENTE'))->toBeNull();
});

test('consultarApi registra la solicitud como no encontrada y no crea snapshot cuando la API no encuentra la OC', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response([], 200)]);

    $resultado = $this->servicio->consultarApi('OC-NO-EXISTE');

    expect($resultado['encontrada'])->toBeFalse();
    expect($resultado['snapshot'])->toBeNull();
    expect($resultado['solicitud'])->toBeInstanceOf(SolicitudApiExterna::class);
    expect($resultado['solicitud']->estado)->toBe('no_encontrada');
    expect(SnapshotDatosExterno::count())->toBe(0);
});

test('consultarApi no confunde una respuesta de error de Mercado Público (ticket inválido) con una OC encontrada', function () {
    // Mercado Público responde con un "Codigo" numérico genérico ajeno al código de OC
    // consultado cuando el ticket es inválido o falta, sin Estado/Comprador/Items reales.
    Http::fake(['*/ordenesdecompra.json*' => Http::response(['Codigo' => 203, 'Mensaje' => 'Ticket invalido'], 200)]);

    $resultado = $this->servicio->consultarApi('2182-130-CM26');

    expect($resultado['encontrada'])->toBeFalse();
    expect($resultado['snapshot'])->toBeNull();
    expect($resultado['solicitud']->estado)->toBe('no_encontrada');
    expect(SnapshotDatosExterno::count())->toBe(0);
});

test('consultarApi registra la solicitud y el snapshot cuando la API encuentra la OC', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-NUEVA-001'), 200)]);

    $resultado = $this->servicio->consultarApi('OC-NUEVA-001');

    expect($resultado['encontrada'])->toBeTrue();
    expect($resultado['solicitud']->estado)->toBe('exitosa');
    expect($resultado['snapshot'])->toBeInstanceOf(SnapshotDatosExterno::class);
    expect($resultado['snapshot']->payload_crudo['Listado'][0]['Codigo'])->toBe('OC-NUEVA-001');
    expect($resultado['payload_normalizado']['organismo_comprador']['nombre'])->toBe('Corporación Administrativa del Poder Judicial');
    expect($resultado['payload_normalizado']['items'])->toHaveCount(2);
});

test('el cronograma conserva la fecha y hora reales de cada hito, sin truncarlas al día', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-CRONOGRAMA-HORA'), 200)]);

    $resultado = $this->servicio->consultarApi('OC-CRONOGRAMA-HORA');

    expect($resultado['payload_normalizado']['cronograma'])->toBe([
        ['estado' => 'Enviada', 'fecha' => '2026-04-20 09:15:00'],
        ['estado' => 'Aceptada', 'fecha' => '2026-05-01 14:30:00'],
    ]);
});

test('compararConApi no encuentra diferencias cuando el registro local coincide con la API', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-IGUAL-001'), 200)]);

    $oc = OrdenCompraMercadoPublico::factory()->create([
        'codigo' => 'OC-IGUAL-001',
        'estado_mercado_publico' => 'Aceptada',
        'moneda' => 'CLP',
        'forma_pago' => '2',
        'plazo_entrega_dias' => null,
        'monto_neto' => 100000,
        'monto_total' => 119000,
        'fecha_emision' => '2026-04-20',
        'organismo_comprador' => [
            'nombre' => 'Corporación Administrativa del Poder Judicial',
            'unidad' => 'Corte de Apelaciones',
            'rut' => '60.503.000-9',
        ],
        'cronograma' => [
            ['estado' => 'Enviada', 'fecha' => '2026-04-20 09:15:00'],
            ['estado' => 'Aceptada', 'fecha' => '2026-05-01 14:30:00'],
        ],
    ]);

    $resultado = $this->servicio->compararConApi($oc);

    expect($resultado['encontrada'])->toBeTrue();
    expect($resultado['diferencias'])->toBe([]);
});

test('compararConApi detecta diferencias entre el registro local y la API', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-DISTINTA-001', ['Estado' => 'Modificada']), 200)]);

    $oc = OrdenCompraMercadoPublico::factory()->create([
        'codigo' => 'OC-DISTINTA-001',
        'estado_mercado_publico' => 'Aceptada',
    ]);

    $resultado = $this->servicio->compararConApi($oc);

    expect($resultado['encontrada'])->toBeTrue();
    expect($resultado['diferencias'])->toHaveKey('estado_mercado_publico');
    expect($resultado['diferencias']['estado_mercado_publico']['local'])->toBe('Aceptada');
    expect($resultado['diferencias']['estado_mercado_publico']['api'])->toBe('Modificada');
});

test('verificarProveedor encuentra el proveedor existente sin importar el formato del rut, y retorna null si no existe', function () {
    // El proveedor local queda guardado sin puntos (normalizado); Mercado Público
    // entrega el RUT con puntos. Antes del fix, esta diferencia de formato hacía
    // creer que el proveedor no existía y terminaba duplicándolo.
    Proveedor::create(['rutproveedor' => '76123456-7', 'nombre' => 'Proveedor de Prueba SpA', 'activo' => true]);

    $payload = ['proveedor' => ['rut' => '76.123.456-7', 'nombre' => 'Proveedor de Prueba SpA']];
    expect($this->servicio->verificarProveedor($payload)?->rutproveedor)->toBe('76123456-7');

    $payloadSinProveedor = ['proveedor' => ['rut' => '1-9', 'nombre' => 'Otro']];
    expect($this->servicio->verificarProveedor($payloadSinProveedor))->toBeNull();
});

test('guardarDesdeApi crea la OC, sus ítems y queda vinculada al snapshot que la originó, usando el proveedor indicado como override', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-GUARDAR-001'), 200)]);

    $resultado = $this->servicio->consultarApi('OC-GUARDAR-001');
    $proveedor = Proveedor::create(['rutproveedor' => '76.123.456-7', 'nombre' => 'Proveedor de Prueba SpA', 'activo' => true]);

    ['orden' => $oc, 'proveedor_resultado' => $proveedorResultado] = $this->servicio->guardarDesdeApi(
        $resultado['payload_normalizado'],
        $resultado['snapshot'],
        proveedorIdOverride: $proveedor->id,
    );

    expect($oc->codigo)->toBe('OC-GUARDAR-001');
    expect($oc->proveedor_id)->toBe($proveedor->id);
    expect($oc->snapshot_datos_externo_id)->toBe($resultado['snapshot']->id);
    expect($oc->items)->toHaveCount(2);
    expect($proveedorResultado)->toBe('sin_cambios');
});

test('guardarDesdeApi crea el proveedor automáticamente cuando no existe en el catálogo', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-PROVEEDOR-NUEVO'), 200)]);

    $resultado = $this->servicio->consultarApi('OC-PROVEEDOR-NUEVO');

    ['orden' => $oc, 'proveedor_resultado' => $proveedorResultado] = $this->servicio->guardarDesdeApi(
        $resultado['payload_normalizado'],
        $resultado['snapshot'],
    );

    expect($proveedorResultado)->toBe('creado');
    expect($oc->proveedor->rutproveedor)->toBe('76123456-7');
    expect($oc->proveedor->nombre)->toBe('Proveedor de Prueba SpA');
});

test('guardarDesdeApi completa el nombre vacío de un proveedor existente sin sobrescribir otros campos', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-PROVEEDOR-INCOMPLETO'), 200)]);

    $proveedor = Proveedor::create(['rutproveedor' => '76.123.456-7', 'nombre' => '', 'correo' => 'contacto@proveedor.cl', 'activo' => true]);
    $resultado = $this->servicio->consultarApi('OC-PROVEEDOR-INCOMPLETO');

    ['orden' => $oc, 'proveedor_resultado' => $proveedorResultado] = $this->servicio->guardarDesdeApi(
        $resultado['payload_normalizado'],
        $resultado['snapshot'],
    );

    expect($proveedorResultado)->toBe('actualizado');
    expect($oc->proveedor_id)->toBe($proveedor->id);
    expect($oc->proveedor->nombre)->toBe('Proveedor de Prueba SpA');
    expect($oc->proveedor->correo)->toBe('contacto@proveedor.cl');
});

test('guardarDesdeApi no modifica un proveedor existente que ya tiene sus campos completos', function () {
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-PROVEEDOR-COMPLETO'), 200)]);

    $proveedor = Proveedor::create(['rutproveedor' => '76.123.456-7', 'nombre' => 'Nombre Ya Cargado SpA', 'activo' => true]);
    $resultado = $this->servicio->consultarApi('OC-PROVEEDOR-COMPLETO');

    ['orden' => $oc, 'proveedor_resultado' => $proveedorResultado] = $this->servicio->guardarDesdeApi(
        $resultado['payload_normalizado'],
        $resultado['snapshot'],
    );

    expect($proveedorResultado)->toBe('sin_cambios');
    expect($oc->proveedor_id)->toBe($proveedor->id);
    expect($oc->proveedor->nombre)->toBe('Nombre Ya Cargado SpA');
});

test('guardarDesdeApi revierte la creación del proveedor si el guardado de la OC falla', function () {
    // Fuerza el fallo del guardado de la OC violando su unicidad de código
    // (ya existe una OC con ese mismo código), después de que el proveedor
    // ya se creó dentro de la misma transacción.
    OrdenCompraMercadoPublico::factory()->create(['codigo' => 'OC-FALLA-TRANSACCION']);
    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-FALLA-TRANSACCION'), 200)]);

    $resultado = $this->servicio->consultarApi('OC-FALLA-TRANSACCION');

    expect(fn () => $this->servicio->guardarDesdeApi($resultado['payload_normalizado'], $resultado['snapshot']))
        ->toThrow(Exception::class);

    expect(Proveedor::where('rutproveedor', '76123456-7')->exists())->toBeFalse();
});

test('aplicarActualizacion sobrescribe los campos y reemplaza los ítems del registro local', function () {
    $oc = OrdenCompraMercadoPublico::factory()
        ->has(OrdenCompraMercadoPublicoItem::factory()->count(1), 'items')
        ->create(['codigo' => 'OC-ACTUALIZAR-001', 'estado_mercado_publico' => 'Enviada']);

    Http::fake(['*/ordenesdecompra.json*' => Http::response(payloadCrudoOcMercadoPublico('OC-ACTUALIZAR-001'), 200)]);
    $resultado = $this->servicio->compararConApi($oc);

    $actualizada = $this->servicio->aplicarActualizacion($oc, $resultado['payload_normalizado'], $resultado['snapshot']);

    expect($actualizada->estado_mercado_publico)->toBe('Aceptada');
    expect($actualizada->items)->toHaveCount(2);
});

test('resolverUrlPdf extrae el enlace real de descarga desde la página pública de Mercado Público', function () {
    $htmlConBotonPdf = '<html><body><input type="image" name="imgPDF" id="imgPDF" src="../../Includes/images/ic_descargar_pdf.gif" onclick="open(&#39;PDFReport.aspx?qs=MSuLJTDGpV25BLIThmvKMQ==&#39;,&#39;MercadoPublico&#39;, &#39;width=750&#39;);window.event.returnValue=false;" /></body></html>';

    Http::fake([
        '*/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx*' => Http::response($htmlConBotonPdf, 200),
    ]);

    $url = $this->servicio->resolverUrlPdf('2182-99-AG26');

    expect($url)->toBe('https://www.mercadopublico.cl/PurchaseOrder/Modules/PO/PDFReport.aspx?qs=MSuLJTDGpV25BLIThmvKMQ==');
    expect(SolicitudApiExterna::first()->estado)->toBe('exitosa');
});

test('resolverUrlPdf retorna null y registra la solicitud como no encontrada si la página no trae el botón de PDF', function () {
    Http::fake([
        '*/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx*' => Http::response('<html><body>OC no encontrada</body></html>', 200),
    ]);

    $url = $this->servicio->resolverUrlPdf('OC-INEXISTENTE-MP');

    expect($url)->toBeNull();
    expect(SolicitudApiExterna::first()->estado)->toBe('no_encontrada');
});

test('resolverUrlPdf retorna null y registra la solicitud como error si la petición HTTP falla', function () {
    Http::fake([
        '*/PurchaseOrder/Modules/PO/DetailsPurchaseOrder.aspx*' => fn () => throw new ConnectionException('timeout'),
    ]);

    $url = $this->servicio->resolverUrlPdf('2182-99-AG26');

    expect($url)->toBeNull();
    expect(SolicitudApiExterna::first()->estado)->toBe('error');
});
