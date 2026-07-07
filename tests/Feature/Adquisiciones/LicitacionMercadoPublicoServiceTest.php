<?php

use App\Models\LicitacionMercadoPublico;
use App\Models\LicitacionMercadoPublicoItem;
use App\Models\Proveedor;
use App\Models\SnapshotDatosExterno;
use App\Models\SolicitudApiExterna;
use App\Services\Adquisiciones\LicitacionMercadoPublicoService;
use Database\Seeders\IntegracionesSeeder;
use Illuminate\Support\Facades\Http;

/**
 * @return array<string, mixed>
 */
function licitacionCrudaMercadoPublico(string $codigo, array $overrides = []): array
{
    return array_merge([
        'CodigoExterno' => $codigo,
        'Nombre' => 'NEUMÁTICOS PARA EQUIPOS VARIOS',
        'Estado' => 'Publicada',
        'CodigoEstado' => 5,
        'Moneda' => 'CLP',
        'MontoEstimado' => null,
        'Comprador' => [
            'NombreOrganismo' => 'Corporación Administrativa del Poder Judicial',
            'NombreUnidad' => 'Corte de Apelaciones',
            'RutUnidad' => '60.503.000-9',
        ],
        'Fechas' => [
            'FechaCreacion' => '2026-07-02T09:10:56.203',
            'FechaCierre' => '2026-07-17T15:30:00',
            'FechaInicio' => '2026-07-06T12:40:00',
            'FechaFinal' => '2026-07-11T12:30:00',
            'FechaPubRespuestas' => '2026-07-13T12:30:00',
            'FechaActoAperturaTecnica' => '2026-07-17T15:40:00',
            'FechaActoAperturaEconomica' => '2026-07-17T15:40:00',
            'FechaPublicacion' => '2026-07-06T11:12:19.3',
            'FechaAdjudicacion' => null,
            'FechaEstimadaAdjudicacion' => '2026-07-27T17:00:00',
        ],
        'Adjudicacion' => null,
        'Items' => [
            'Listado' => [
                [
                    'Correlativo' => 1,
                    'CodigoProducto' => 25172503,
                    'Categoria' => 'Vehículos y equipamiento en general / Neumáticos',
                    'NombreProducto' => 'Neumáticos para camiones pesados',
                    'Descripcion' => 'Neumático 275/70R22.5 traccional',
                    'UnidadMedida' => 'Unidad',
                    'Cantidad' => 7.0,
                    'Adjudicacion' => null,
                ],
                [
                    'Correlativo' => 2,
                    'CodigoProducto' => 25172503,
                    'Categoria' => 'Vehículos y equipamiento en general / Neumáticos',
                    'NombreProducto' => 'Neumáticos para camiones pesados',
                    'Descripcion' => 'Neumático 12R22,5 traccional',
                    'UnidadMedida' => 'Unidad',
                    'Cantidad' => 20.0,
                    'Adjudicacion' => null,
                ],
            ],
        ],
    ], $overrides);
}

/**
 * Envuelve la licitación en la forma real de la respuesta de Mercado Público:
 * `{"Cantidad": 1, "Listado": [{...}]}`.
 *
 * @return array<string, mixed>
 */
function payloadCrudoLicitacionMercadoPublico(string $codigo, array $overrides = []): array
{
    return [
        'Cantidad' => 1,
        'Version' => 'v1',
        'Listado' => [licitacionCrudaMercadoPublico($codigo, $overrides)],
    ];
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->servicio = app(LicitacionMercadoPublicoService::class);
});

test('buscarLocal encuentra una Licitación existente por código y retorna null si no existe', function () {
    LicitacionMercadoPublico::factory()->create(['codigo' => 'LIC-LOCAL-001']);

    expect($this->servicio->buscarLocal('LIC-LOCAL-001'))->not->toBeNull();
    expect($this->servicio->buscarLocal('LIC-INEXISTENTE'))->toBeNull();
});

test('consultarApi registra la solicitud como no encontrada y no crea snapshot cuando la API no encuentra la licitación', function () {
    Http::fake(['*/licitaciones.json*' => Http::response([], 200)]);

    $resultado = $this->servicio->consultarApi('LIC-NO-EXISTE');

    expect($resultado['encontrada'])->toBeFalse();
    expect($resultado['snapshot'])->toBeNull();
    expect($resultado['solicitud'])->toBeInstanceOf(SolicitudApiExterna::class);
    expect($resultado['solicitud']->estado)->toBe('no_encontrada');
    expect(SnapshotDatosExterno::count())->toBe(0);
});

test('consultarApi no confunde una respuesta de error de Mercado Público (ticket inválido) con una Licitación encontrada', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(['Codigo' => 203, 'Mensaje' => 'Ticket invalido'], 200)]);

    $resultado = $this->servicio->consultarApi('1004-34-LE26');

    expect($resultado['encontrada'])->toBeFalse();
    expect($resultado['snapshot'])->toBeNull();
    expect($resultado['solicitud']->estado)->toBe('no_encontrada');
    expect(SnapshotDatosExterno::count())->toBe(0);
});

test('consultarApi registra la solicitud y el snapshot cuando la API encuentra la Licitación', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-NUEVA-001'), 200)]);

    $resultado = $this->servicio->consultarApi('LIC-NUEVA-001');

    expect($resultado['encontrada'])->toBeTrue();
    expect($resultado['solicitud']->estado)->toBe('exitosa');
    expect($resultado['snapshot'])->toBeInstanceOf(SnapshotDatosExterno::class);
    expect($resultado['snapshot']->payload_crudo['Listado'][0]['CodigoExterno'])->toBe('LIC-NUEVA-001');
    expect($resultado['payload_normalizado']['organismo_comprador']['nombre'])->toBe('Corporación Administrativa del Poder Judicial');
    expect($resultado['payload_normalizado']['items'])->toHaveCount(2);
});

test('el cronograma conserva la fecha y hora reales de cada hito informado, y omite los no informados', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-CRONOGRAMA-001'), 200)]);

    $resultado = $this->servicio->consultarApi('LIC-CRONOGRAMA-001');

    expect($resultado['payload_normalizado']['cronograma'])->toBe([
        ['estado' => 'Creada', 'fecha' => '2026-07-02T09:10:56.203'],
        ['estado' => 'Publicada', 'fecha' => '2026-07-06T11:12:19.3'],
        ['estado' => 'Inicio de preguntas', 'fecha' => '2026-07-06T12:40:00'],
        ['estado' => 'Cierre de preguntas', 'fecha' => '2026-07-11T12:30:00'],
        ['estado' => 'Publicación de respuestas', 'fecha' => '2026-07-13T12:30:00'],
        ['estado' => 'Cierre de recepción de ofertas', 'fecha' => '2026-07-17T15:30:00'],
        ['estado' => 'Apertura técnica', 'fecha' => '2026-07-17T15:40:00'],
        ['estado' => 'Apertura económica', 'fecha' => '2026-07-17T15:40:00'],
        // FechaAdjudicacion viene null, por lo que se usa FechaEstimadaAdjudicacion como respaldo.
        ['estado' => 'Adjudicación', 'fecha' => '2026-07-27T17:00:00'],
    ]);
});

test('la adjudicación de un ítem se conserva como dato informativo sin crear ni tocar ningún Proveedor', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-ADJUDICADA-001', [
        'Estado' => 'Adjudicada',
        'CodigoEstado' => 8,
        'Adjudicacion' => [
            'Tipo' => 2,
            'Fecha' => '2026-07-06T00:00:00',
            'Numero' => '4045',
            'NumeroOferentes' => 1,
            'UrlActa' => 'http://www.mercadopublico.cl/Procurement/Modules/RFB/StepsProcessAward/PreviewAwardAct.aspx?qs=abc',
        ],
        'Items' => [
            'Listado' => [
                [
                    'Correlativo' => 1,
                    'CodigoProducto' => 80111606,
                    'Categoria' => 'Servicios profesionales',
                    'NombreProducto' => 'Personal médico temporal',
                    'Descripcion' => 'Médico especialista',
                    'UnidadMedida' => 'Unidad',
                    'Cantidad' => 1,
                    'Adjudicacion' => [
                        'RutProveedor' => '99.578.780-6',
                        'NombreProveedor' => 'AMPATAGONIA SERVICIOS MEDICOS LTDA.',
                        'Cantidad' => 28500000,
                        'MontoUnitario' => 1,
                    ],
                ],
            ],
        ],
    ]), 200)]);

    $resultado = $this->servicio->consultarApi('LIC-ADJUDICADA-001');

    expect($resultado['payload_normalizado']['adjudicacion'])->toBe([
        'tipo' => 2,
        'fecha' => '2026-07-06T00:00:00',
        'numero' => '4045',
        'numero_oferentes' => 1,
        'url_acta' => 'http://www.mercadopublico.cl/Procurement/Modules/RFB/StepsProcessAward/PreviewAwardAct.aspx?qs=abc',
    ]);
    expect($resultado['payload_normalizado']['items'][0]['adjudicacion'])->toBe([
        'rut_proveedor' => '99.578.780-6',
        'nombre_proveedor' => 'AMPATAGONIA SERVICIOS MEDICOS LTDA.',
        'cantidad' => 28500000.0,
        'monto_unitario' => 1.0,
    ]);

    $licitacion = $this->servicio->guardarDesdeApi($resultado['payload_normalizado'], $resultado['snapshot']);

    expect($licitacion['licitacion']->items[0]->adjudicacion['rut_proveedor'])->toBe('99.578.780-6');
    expect(Proveedor::count())->toBe(0);
});

test('compararConApi no encuentra diferencias cuando el registro local coincide con la API', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-IGUAL-001'), 200)]);

    $licitacion = LicitacionMercadoPublico::factory()->create([
        'codigo' => 'LIC-IGUAL-001',
        'nombre' => 'NEUMÁTICOS PARA EQUIPOS VARIOS',
        'estado_mercado_publico' => 'Publicada',
        'codigo_estado_mercado_publico' => 5,
        'moneda' => 'CLP',
        'monto_estimado' => null,
        'organismo_comprador' => [
            'nombre' => 'Corporación Administrativa del Poder Judicial',
            'unidad' => 'Corte de Apelaciones',
            'rut' => '60.503.000-9',
        ],
        'cronograma' => [
            ['estado' => 'Creada', 'fecha' => '2026-07-02T09:10:56.203'],
            ['estado' => 'Publicada', 'fecha' => '2026-07-06T11:12:19.3'],
            ['estado' => 'Inicio de preguntas', 'fecha' => '2026-07-06T12:40:00'],
            ['estado' => 'Cierre de preguntas', 'fecha' => '2026-07-11T12:30:00'],
            ['estado' => 'Publicación de respuestas', 'fecha' => '2026-07-13T12:30:00'],
            ['estado' => 'Cierre de recepción de ofertas', 'fecha' => '2026-07-17T15:30:00'],
            ['estado' => 'Apertura técnica', 'fecha' => '2026-07-17T15:40:00'],
            ['estado' => 'Apertura económica', 'fecha' => '2026-07-17T15:40:00'],
            ['estado' => 'Adjudicación', 'fecha' => '2026-07-27T17:00:00'],
        ],
        'adjudicacion' => null,
    ]);

    $resultado = $this->servicio->compararConApi($licitacion);

    expect($resultado['encontrada'])->toBeTrue();
    expect($resultado['diferencias'])->toBe([]);
});

test('compararConApi detecta diferencias entre el registro local y la API', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-DISTINTA-001', ['Estado' => 'Adjudicada']), 200)]);

    $licitacion = LicitacionMercadoPublico::factory()->create([
        'codigo' => 'LIC-DISTINTA-001',
        'estado_mercado_publico' => 'Publicada',
    ]);

    $resultado = $this->servicio->compararConApi($licitacion);

    expect($resultado['encontrada'])->toBeTrue();
    expect($resultado['diferencias'])->toHaveKey('estado_mercado_publico');
    expect($resultado['diferencias']['estado_mercado_publico']['local'])->toBe('Publicada');
    expect($resultado['diferencias']['estado_mercado_publico']['api'])->toBe('Adjudicada');
});

test('guardarDesdeApi crea la Licitación, sus ítems y queda vinculada al snapshot que la originó', function () {
    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-GUARDAR-001'), 200)]);

    $resultado = $this->servicio->consultarApi('LIC-GUARDAR-001');

    $guardado = $this->servicio->guardarDesdeApi($resultado['payload_normalizado'], $resultado['snapshot']);

    expect($guardado['licitacion']->codigo)->toBe('LIC-GUARDAR-001');
    expect($guardado['licitacion']->snapshot_datos_externo_id)->toBe($resultado['snapshot']->id);
    expect($guardado['licitacion']->items)->toHaveCount(2);
});

test('guardarDesdeApi no crea un segundo registro para un código de licitación duplicado', function () {
    LicitacionMercadoPublico::factory()->create(['codigo' => 'LIC-DUPLICADA-001']);

    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-DUPLICADA-001'), 200)]);
    $resultado = $this->servicio->consultarApi('LIC-DUPLICADA-001');

    expect(fn () => $this->servicio->guardarDesdeApi($resultado['payload_normalizado'], $resultado['snapshot']))
        ->toThrow(Exception::class);

    expect(LicitacionMercadoPublico::where('codigo', 'LIC-DUPLICADA-001')->count())->toBe(1);
});

test('aplicarActualizacion sobrescribe los campos y reemplaza los ítems del registro local', function () {
    $licitacion = LicitacionMercadoPublico::factory()
        ->has(LicitacionMercadoPublicoItem::factory()->count(1), 'items')
        ->create(['codigo' => 'LIC-ACTUALIZAR-001', 'estado_mercado_publico' => 'Publicada']);

    Http::fake(['*/licitaciones.json*' => Http::response(payloadCrudoLicitacionMercadoPublico('LIC-ACTUALIZAR-001', ['Estado' => 'Adjudicada']), 200)]);
    $resultado = $this->servicio->compararConApi($licitacion);

    $actualizada = $this->servicio->aplicarActualizacion($licitacion, $resultado['payload_normalizado'], $resultado['snapshot']);

    expect($actualizada->estado_mercado_publico)->toBe('Adjudicada');
    expect($actualizada->items)->toHaveCount(2);
});
