<?php

use App\Exceptions\ConectorAutomatizacionNoAutorizadoException;
use App\Models\CasoPagoProveedor;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\Documento;
use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use App\Services\Sgf\ConectorSgfPlaywrightService;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    config(['services.sgf_playwright.base_url' => 'http://sgf-playwright.test', 'services.sgf_playwright.api_key' => 'test-key']);
    $this->servicio = app(ConectorSgfPlaywrightService::class);
});

function autorizarConectorSgfDePrueba(): void
{
    $usuario = User::factory()->create();

    ConectorAutomatizacionNavegador::where('codigo', 'SGF_PLAYWRIGHT')->firstOrFail()->update([
        'activo' => true,
        'autorizado_por' => $usuario->id,
        'autorizado_en' => now(),
    ]);
}

test('verificarCaso lanza si el conector de SGF no está autorizado y no crea trabajo_integracion', function () {
    expect(fn () => $this->servicio->verificarCaso('12345'))
        ->toThrow(ConectorAutomatizacionNoAutorizadoException::class);

    expect(TrabajoIntegracion::count())->toBe(0);
});

test('verificarCaso registra snapshot, pasos y finaliza completado cuando SGF encuentra el caso', function () {
    autorizarConectorSgfDePrueba();

    Http::fake(['*/casos/verificar' => Http::response([
        'encontrada' => true,
        'payload_crudo' => ['sgf_id' => '12345', 'estado' => 'EN_TRAMITE', 'rut' => '11111111-1', 'monto' => '500.000'],
        'pasos' => [
            ['orden' => 1, 'accion' => 'navegar_a_ficha', 'estado' => 'completado'],
            ['orden' => 2, 'accion' => 'extraer_datos', 'estado' => 'completado'],
        ],
    ], 200)]);

    $resultado = $this->servicio->verificarCaso('12345');

    expect($resultado['encontrada'])->toBeTrue();
    expect($resultado['snapshot']->referencia_externa)->toBe('12345');
    expect($resultado['snapshot']->payload_normalizado['monto'])->toEqual(500000.0);

    $trabajo = TrabajoIntegracion::sole();
    expect($trabajo->tipo)->toBe('verificar_caso');
    expect($trabajo->mecanismo)->toBe('playwright');
    expect($trabajo->estado)->toBe('completado');
    expect($trabajo->total_elementos)->toBe(1);

    $ejecucion = $trabajo->ejecucionesAutomatizacionNavegador()->sole();
    expect($ejecucion->estado)->toBe('completado');
    expect($ejecucion->pasos)->toHaveCount(2);

    $caso = CasoPagoProveedor::where('sgf_id', '12345')->sole();
    expect($caso->proceso->estadoActual->codigo)->toBe('importada_desde_sgf');
});

test('verificarCaso no crea snapshot y finaliza completado cuando SGF no encuentra el caso', function () {
    autorizarConectorSgfDePrueba();

    Http::fake(['*/casos/verificar' => Http::response(['encontrada' => false, 'pasos' => []], 200)]);

    $resultado = $this->servicio->verificarCaso('sin-existencia');

    expect($resultado['encontrada'])->toBeFalse();
    expect($resultado['snapshot'])->toBeNull();

    $trabajo = TrabajoIntegracion::sole();
    expect($trabajo->estado)->toBe('completado');
    expect($trabajo->total_elementos)->toBe(0);
});

test('verificarCaso no crea snapshot y finaliza en error cuando el microservicio falla', function () {
    autorizarConectorSgfDePrueba();

    Http::fake(['*/casos/verificar' => Http::response(['error' => 'sesión de SGF expirada'], 500)]);

    $resultado = $this->servicio->verificarCaso('12345');

    expect($resultado['encontrada'])->toBeFalse();
    expect($resultado['snapshot'])->toBeNull();

    $trabajo = TrabajoIntegracion::sole();
    expect($trabajo->estado)->toBe('error');
    expect($trabajo->error)->toBe('sesión de SGF expirada');

    $ejecucion = $trabajo->ejecucionesAutomatizacionNavegador()->sole();
    expect($ejecucion->estado)->toBe('error');
});

test('importarPendientes registra un snapshot por fila y vincula documentos entregados', function () {
    autorizarConectorSgfDePrueba();

    Http::fake(['*/casos/importar-pendientes' => Http::response([
        'filas' => [
            ['sgf_id' => '111', 'payload_crudo' => ['sgf_id' => '111', 'estado' => 'EN_TRAMITE', 'rut' => '11111111-1', 'monto' => '100.000']],
            [
                'sgf_id' => '222',
                'payload_crudo' => [
                    'sgf_id' => '222',
                    'estado' => 'PAGADA',
                    'rut' => '22222222-2',
                    'monto' => '200.000',
                    'documentos' => [
                        ['tipo_documento_codigo' => 'FACTURA', 'nombre_archivo' => 'factura.pdf', 'ruta_archivo' => 'sgf/factura.pdf'],
                    ],
                ],
            ],
        ],
        'pasos' => [['orden' => 1, 'accion' => 'listar_pendientes', 'estado' => 'completado']],
    ], 200)]);

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now(),
    ]);

    $this->servicio->importarPendientes($trabajo);

    $trabajo->refresh();
    expect($trabajo->estado)->toBe('completado');
    expect($trabajo->total_elementos)->toBe(2);
    expect($trabajo->snapshotsDatosExternos)->toHaveCount(2);

    $snapshotSinDocumentos = $trabajo->snapshotsDatosExternos->firstWhere('referencia_externa', '111');
    expect($snapshotSinDocumentos->documentos)->toHaveCount(0);

    $snapshotConDocumentos = $trabajo->snapshotsDatosExternos->firstWhere('referencia_externa', '222');
    expect($snapshotConDocumentos->documentos)->toHaveCount(1);

    $documento = Documento::find($snapshotConDocumentos->documentos->first()->documento_id);
    expect($documento->tipoDocumento->codigo)->toBe('FACTURA');
    expect($documento->versiones->first()->nombre_archivo)->toBe('factura.pdf');

    expect(CasoPagoProveedor::where('sgf_id', '111')->exists())->toBeTrue();
    expect(CasoPagoProveedor::where('sgf_id', '222')->exists())->toBeTrue();
});

test('importarPendientes no guarda snapshots parciales cuando el microservicio falla', function () {
    autorizarConectorSgfDePrueba();

    Http::fake(['*/casos/importar-pendientes' => Http::response(['error' => 'timeout de navegación'], 500)]);

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now(),
    ]);

    $this->servicio->importarPendientes($trabajo);

    $trabajo->refresh();
    expect($trabajo->estado)->toBe('error');
    expect($trabajo->snapshotsDatosExternos)->toHaveCount(0);
});
