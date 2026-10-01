<?php

use App\Models\CasoPagoProveedor;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\Documento;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use App\Models\VersionDocumento;
use App\Services\Sgf\ConectorSgfPlaywrightService;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    config(['services.sgf_playwright.base_url' => 'http://sgf-playwright.test', 'services.sgf_playwright.api_key' => 'test-key']);
    Storage::fake('local');
    $this->servicio = app(ConectorSgfPlaywrightService::class);

    $usuario = User::factory()->create();
    ConectorAutomatizacionNavegador::where('codigo', 'SGF_PLAYWRIGHT')->firstOrFail()->update([
        'activo' => true,
        'autorizado_por' => $usuario->id,
        'autorizado_en' => now(),
    ]);
});

function importarCasoSgfConDocumento(string $nombre, string $ruta): void
{
    Http::fake(['*/casos/importar-pendientes' => Http::response([
        'filas' => [[
            'sgf_id' => '333',
            'payload_crudo' => [
                'sgf_id' => '333',
                'estado' => 'PAGADA',
                'rut' => '33333333-3',
                'monto' => '300.000',
                'documentos' => [
                    ['tipo_documento_codigo' => 'FACTURA', 'nombre_archivo' => $nombre, 'ruta_archivo' => $ruta],
                ],
            ],
        ]],
        'pasos' => [['orden' => 1, 'accion' => 'listar_pendientes', 'estado' => 'completado']],
    ], 200)]);

    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => SistemaExterno::where('codigo', 'SGF')->firstOrFail()->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now(),
    ]);

    app(ConectorSgfPlaywrightService::class)->importarPendientes($trabajo);
}

test('un documento con mojibake se registra con nombre y ruta reparados y mueve el archivo físico', function () {
    Storage::disk('local')->put('sgf-documentos/333/CT NÂ°957.pdf', 'contenido');

    importarCasoSgfConDocumento('CT NÂ°957.pdf', 'sgf-documentos/333/CT NÂ°957.pdf');

    $documento = Documento::sole();
    $version = VersionDocumento::sole();

    expect($documento->titulo)->toBe('CT N°957.pdf')
        ->and($version->nombre_archivo)->toBe('CT N°957.pdf')
        ->and($version->ruta_archivo)->toBe('sgf-documentos/333/CT N°957.pdf');

    Storage::disk('local')->assertExists('sgf-documentos/333/CT N°957.pdf');
    Storage::disk('local')->assertMissing('sgf-documentos/333/CT NÂ°957.pdf');
});

test('el payload crudo del snapshot conserva el nombre tal como lo entregó el conector', function () {
    importarCasoSgfConDocumento('CT NÂ°957.pdf', 'sgf-documentos/333/CT NÂ°957.pdf');

    $snapshot = SnapshotDatosExterno::where('referencia_externa', '333')->sole();

    expect($snapshot->payload_crudo['documentos'][0]['nombre_archivo'])->toBe('CT NÂ°957.pdf')
        ->and($snapshot->payload_crudo['documentos'][0]['ruta_archivo'])->toBe('sgf-documentos/333/CT NÂ°957.pdf');
});

test('si el archivo físico no existe igual se registra el documento con el nombre reparado', function () {
    importarCasoSgfConDocumento('pasaje_aÃ©reo.pdf', 'sgf-documentos/333/pasaje_aÃ©reo.pdf');

    expect(VersionDocumento::sole()->ruta_archivo)->toBe('sgf-documentos/333/pasaje_aéreo.pdf');
});

test('un nombre sin mojibake se registra sin cambios', function () {
    importarCasoSgfConDocumento('Garantía.pdf', 'sgf-documentos/333/Garantía.pdf');

    expect(VersionDocumento::sole()->nombre_archivo)->toBe('Garantía.pdf');
});

test('no sobrescribe un archivo existente en el destino al mover', function () {
    Storage::disk('local')->put('sgf-documentos/333/CT NÂ°957.pdf', 'corrupto');
    Storage::disk('local')->put('sgf-documentos/333/CT N°957.pdf', 'ya existente');

    importarCasoSgfConDocumento('CT NÂ°957.pdf', 'sgf-documentos/333/CT NÂ°957.pdf');

    expect(Storage::disk('local')->get('sgf-documentos/333/CT N°957.pdf'))->toBe('ya existente');
    Storage::disk('local')->assertExists('sgf-documentos/333/CT NÂ°957.pdf');
});

test('reimportar con el nombre reparado no duplica un documento previo registrado con ruta corrupta', function () {
    importarCasoSgfConDocumento('CT NÂ°957.pdf', 'sgf-documentos/333/CT NÂ°957.pdf');

    // Simula un registro previo a este change: la ruta quedó corrupta en BD.
    VersionDocumento::sole()->update([
        'ruta_archivo' => 'sgf-documentos/333/CT NÂ°957.pdf',
        'nombre_archivo' => 'CT NÂ°957.pdf',
    ]);

    importarCasoSgfConDocumento('CT N°957.pdf', 'sgf-documentos/333/CT N°957.pdf');

    expect(Documento::count())->toBe(1);

    $caso = CasoPagoProveedor::where('sgf_id', '333')->sole();
    expect($caso->proceso->vinculosDocumento()->where('activo', true)->count())->toBe(1);
});

test('reimportar con el nombre corrupto no duplica un documento ya registrado con ruta reparada', function () {
    importarCasoSgfConDocumento('CT NÂ°957.pdf', 'sgf-documentos/333/CT NÂ°957.pdf');
    importarCasoSgfConDocumento('CT NÂ°957.pdf', 'sgf-documentos/333/CT NÂ°957.pdf');

    expect(Documento::count())->toBe(1);
});
