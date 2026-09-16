<?php

use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\Proveedor;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\VinculoDocumento;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

function crearCasoDePruebaParaVincularArchivoExistente(string $sgfId): CasoPagoProveedor
{
    $proveedor = Proveedor::create(['rutproveedor' => fake()->unique()->numerify('########-#'), 'nombre' => 'Proveedor de Prueba SpA']);

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => $sgfId,
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => $proveedor->rutproveedor,
        'monto' => 500000.0,
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

    return app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
}

test('vincular un archivo suelto existente crea Documento, VersionDocumento y VinculoDocumento', function () {
    Storage::fake('local');

    $caso = crearCasoDePruebaParaVincularArchivoExistente('sgf-vincular-existente-1');
    $ruta = "sgf-documentos/{$caso->sgf_id}/factura.pdf";
    Storage::disk('local')->put($ruta, 'contenido real del archivo');

    $tipo = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura', 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.vincular-existente', $caso->proceso),
        ['ruta_archivo' => $ruta, 'tipo_documento_id' => $tipo->id],
    );

    $response->assertSessionHasNoErrors();

    $vinculo = VinculoDocumento::where('vinculable_type', $caso->proceso::class)
        ->where('vinculable_id', $caso->proceso->id)
        ->first();

    expect($vinculo)->not->toBeNull();
    expect($vinculo->activo)->toBeTrue();

    $version = $vinculo->documento->versiones->first();
    expect($version->ruta_archivo)->toBe($ruta);
    expect($version->hash)->toBe(hash_file('sha256', Storage::disk('local')->path($ruta)));
});

test('rechaza vincular una ruta que no está en el conjunto vigente de archivos sueltos', function () {
    Storage::fake('local');

    $caso = crearCasoDePruebaParaVincularArchivoExistente('sgf-vincular-existente-2');
    $tipo = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura', 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.vincular-existente', $caso->proceso),
        ['ruta_archivo' => "sgf-documentos/{$caso->sgf_id}/inexistente.pdf", 'tipo_documento_id' => $tipo->id],
    );

    $response->assertSessionHasErrors('ruta_archivo');
    expect(Documento::count())->toBe(0);
});

test('un usuario sin el permiso documentos.gestionar no puede vincular y queda auditado', function () {
    Storage::fake('local');

    $caso = crearCasoDePruebaParaVincularArchivoExistente('sgf-vincular-existente-3');
    $ruta = "sgf-documentos/{$caso->sgf_id}/factura.pdf";
    Storage::disk('local')->put($ruta, 'contenido real del archivo');
    $tipo = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura', 'activo' => true]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.vincular-existente', $caso->proceso),
        ['ruta_archivo' => $ruta, 'tipo_documento_id' => $tipo->id],
    );

    $response->assertForbidden();
    expect(Documento::count())->toBe(0);
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('vincular un archivo suelto no dispara ninguna transición de workflow sobre el caso', function () {
    Storage::fake('local');

    $caso = crearCasoDePruebaParaVincularArchivoExistente('sgf-vincular-existente-4');
    $ruta = "sgf-documentos/{$caso->sgf_id}/factura.pdf";
    Storage::disk('local')->put($ruta, 'contenido real del archivo');
    $tipo = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura', 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $estadoAntes = $caso->proceso->estado_actual_id;

    $this->actingAs($usuario)->post(
        route('procesos.documentos.vincular-existente', $caso->proceso),
        ['ruta_archivo' => $ruta, 'tipo_documento_id' => $tipo->id],
    );

    expect($caso->proceso->refresh()->estado_actual_id)->toBe($estadoAntes);
});
