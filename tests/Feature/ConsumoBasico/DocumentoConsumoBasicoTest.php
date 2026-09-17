<?php

use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\ConsumoBasico;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Services\ConsumoBasico\ConsumoBasicoService;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\ConsumoBasicoSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function crearCcostoDePruebaParaDocumentoConsumoBasico(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-DCB-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-DCB-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-DCB-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-DCB-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

function crearConsumoBasicoDePrueba(?string $sufijo = null): ConsumoBasico
{
    $sufijo ??= fake()->unique()->numerify('####');
    $proveedor = Proveedor::create(['rutproveedor' => "7680000{$sufijo}-1", 'nombre' => 'EDELAYSEN']);

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => "sgf-documento-{$sufijo}",
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => $proveedor->rutproveedor,
        'monto' => 45000.0,
    ];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);

    $ccosto = crearCcostoDePruebaParaDocumentoConsumoBasico();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => "7777{$sufijo}",
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    return app(ConsumoBasicoService::class)->registrar($caso, [
        'cliente_medidor_id' => $clienteMedidor->id,
        'numero_documento' => 'FAE-3000',
        'numero_medidor' => 'MED-3000',
        'fecha_inicio_lectura' => '2026-06-01',
        'fecha_fin_lectura' => '2026-07-01',
        'fecha_emision' => '2026-07-05',
        'fecha_vencimiento' => '2026-07-20',
        'lectura_estimada' => false,
        'monto_neto' => 40000,
        'iva' => 7600,
        'monto_exento' => 0,
        'saldo_anterior' => 0,
        'monto_total' => 47600,
    ]);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ConsumoBasicoSeeder::class);
    $this->seed(TiposDocumentoSeeder::class);
});

test('subir el PDF de la boleta crea Documento, VersionDocumento y VinculoDocumento', function () {
    Storage::fake('local');

    $consumoBasico = crearConsumoBasicoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('consumo_basico.editar');

    $archivo = UploadedFile::fake()->create('boleta.pdf', 100, 'application/pdf');

    $response = $this->actingAs($usuario)->post(
        route('consumo-basico.documento.store', $consumoBasico),
        ['archivo' => $archivo],
    );

    $response->assertSessionHasNoErrors();

    $consumoBasico->refresh()->load('vinculosDocumento.documento.versiones');
    $vinculo = $consumoBasico->vinculosDocumento->first();

    expect($vinculo)->not->toBeNull();
    expect($vinculo->activo)->toBeTrue();
    expect($vinculo->documento->versiones->first()->hash)->not->toBeEmpty();
});

test('descargar el documento adjunto de un ConsumoBasico funciona sin pasar por Proceso', function () {
    Storage::fake('local');

    $consumoBasico = crearConsumoBasicoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['consumo_basico.editar', 'consumo_basico.ver']);

    $archivo = UploadedFile::fake()->create('boleta.pdf', 100, 'application/pdf');

    $this->actingAs($usuario)->post(
        route('consumo-basico.documento.store', $consumoBasico),
        ['archivo' => $archivo],
    );

    $documento = $consumoBasico->refresh()->vinculosDocumento->first()->documento;

    $response = $this->actingAs($usuario)->get(
        route('consumo-basico.documento.descargar', ['consumoBasico' => $consumoBasico, 'documento' => $documento]),
    );

    $response->assertOk();
});

test('no se puede desvincular el documento de un ConsumoBasico usando el id de un vínculo de otro', function () {
    Storage::fake('local');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['consumo_basico.editar', 'consumo_basico.ver']);

    $consumoAjeno = crearConsumoBasicoDePrueba();
    $this->actingAs($usuario)->post(
        route('consumo-basico.documento.store', $consumoAjeno),
        ['archivo' => UploadedFile::fake()->create('boleta.pdf', 100, 'application/pdf')],
    );
    $vinculoAjeno = $consumoAjeno->refresh()->vinculosDocumento->first();

    $consumoPropio = crearConsumoBasicoDePrueba();

    $response = $this->actingAs($usuario)->delete(
        route('consumo-basico.documento.destroy', ['consumoBasico' => $consumoPropio, 'vinculo' => $vinculoAjeno]),
    );

    $response->assertNotFound();
    expect($vinculoAjeno->refresh()->activo)->toBeTrue();
});

test('no se puede descargar el documento de un ConsumoBasico usando el id de un documento de otro', function () {
    Storage::fake('local');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['consumo_basico.editar', 'consumo_basico.ver']);

    $consumoAjeno = crearConsumoBasicoDePrueba();
    $this->actingAs($usuario)->post(
        route('consumo-basico.documento.store', $consumoAjeno),
        ['archivo' => UploadedFile::fake()->create('boleta.pdf', 100, 'application/pdf')],
    );
    $documentoAjeno = $consumoAjeno->refresh()->vinculosDocumento->first()->documento;

    $consumoPropio = crearConsumoBasicoDePrueba();

    $response = $this->actingAs($usuario)->get(
        route('consumo-basico.documento.descargar', ['consumoBasico' => $consumoPropio, 'documento' => $documentoAjeno]),
    );

    $response->assertNotFound();
});
