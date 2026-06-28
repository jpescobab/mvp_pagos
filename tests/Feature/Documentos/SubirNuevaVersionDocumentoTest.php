<?php

use App\Models\Documento;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Models\VinculoDocumento;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('subir una nueva version de un documento existente crea solo una version nueva', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $archivoOriginal = UploadedFile::fake()->create('v1.pdf', 100, 'application/pdf');

    $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $proceso),
        ['archivo' => $archivoOriginal, 'tipo_documento_id' => $tipoDocumento->id],
    );

    $documento = Documento::first();
    $archivoNuevaVersion = UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.versiones.store', [$proceso, $documento]),
        ['archivo' => $archivoNuevaVersion],
    );

    $response->assertSessionHasNoErrors();
    expect(Documento::count())->toBe(1);
    expect(VinculoDocumento::count())->toBe(1);
    expect($documento->versiones()->count())->toBe(2);
    expect($documento->versiones()->pluck('numero_version')->sort()->values()->all())->toBe([1, 2]);
});

test('subir una nueva version de un documento ya validado no altera su estado vigente', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);
    $documento->validaciones()->create(['estado' => 'valido']);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $archivoNuevaVersion = UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf');

    $this->actingAs($usuario)->post(
        route('procesos.documentos.versiones.store', [$proceso, $documento]),
        ['archivo' => $archivoNuevaVersion],
    );

    expect($documento->refresh()->estadoVigente())->toBe('valido');
});

test('un usuario sin el permiso no puede subir una nueva version y queda auditado como acceso denegado', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();
    $archivoNuevaVersion = UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.versiones.store', [$proceso, $documento]),
        ['archivo' => $archivoNuevaVersion],
    );

    $response->assertForbidden();
    expect($documento->versiones()->count())->toBe(0);
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('descargar un documento con dos versiones sirve la mas reciente', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $proceso),
        ['archivo' => UploadedFile::fake()->create('v1.pdf', 100, 'application/pdf'), 'tipo_documento_id' => $tipoDocumento->id],
    );

    $documento = Documento::first();

    $this->actingAs($usuario)->post(
        route('procesos.documentos.versiones.store', [$proceso, $documento]),
        ['archivo' => UploadedFile::fake()->create('v2.pdf', 100, 'application/pdf')],
    );

    $response = $this->actingAs($usuario)->get(route('procesos.documentos.descargar', [$proceso, $documento]));

    $response->assertOk();

    $rutaVersionMasReciente = $documento->versiones()->latest('numero_version')->first()->ruta_archivo;
    $rutaServida = $response->getFile()->getPathname();

    expect(Storage::disk('local')->path($rutaVersionMasReciente))->toBe($rutaServida);
});
