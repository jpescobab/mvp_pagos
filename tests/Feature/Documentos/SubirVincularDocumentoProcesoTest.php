<?php

use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\Funcionario;
use App\Models\Proceso;
use App\Models\SecurityAuditLog;
use App\Models\TipoDocumento;
use App\Models\User;
use App\Models\VinculoDocumento;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function crearProcesoDePruebaParaDocumentos(): Proceso
{
    $sufijo = fake()->unique()->numerify('####');

    $definicion = DefinicionWorkflow::create(['codigo' => "wf-docs-{$sufijo}", 'nombre' => 'Workflow documentos']);
    $estado = $definicion->estados()->create(['codigo' => 'borrador', 'nombre' => 'Borrador', 'es_inicial' => true]);

    return Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $estado->id,
        'sujeto_type' => Funcionario::class,
        'sujeto_id' => Funcionario::create([
            'rut' => fake()->unique()->numerify('########-#'),
            'nombre' => 'Sujeto de prueba',
        ])->id,
    ]);
}

function crearTipoDocumentoDePrueba(): TipoDocumento
{
    return TipoDocumento::create([
        'codigo' => 'TIPO_DOC_'.fake()->unique()->numerify('####'),
        'nombre' => 'Tipo de prueba',
        'activo' => true,
    ]);
}

test('subir un documento válido crea el documento, su version y lo vincula activo al proceso', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $archivo = UploadedFile::fake()->create('bases.pdf', 100, 'application/pdf');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $proceso),
        ['archivo' => $archivo, 'tipo_documento_id' => $tipoDocumento->id],
    );

    $response->assertSessionHasNoErrors();

    $vinculo = VinculoDocumento::where('vinculable_type', Proceso::class)
        ->where('vinculable_id', $proceso->id)
        ->first();

    expect($vinculo)->not->toBeNull();
    expect($vinculo->activo)->toBeTrue();
    expect($vinculo->documento->tipo_documento_id)->toBe($tipoDocumento->id);
    expect($vinculo->documento->versiones)->toHaveCount(1);
    expect($vinculo->documento->versiones->first()->numero_version)->toBe(1);
});

test('subir un archivo con tipo MIME no permitido es rechazado y no crea ningun registro', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $archivo = UploadedFile::fake()->create('virus.exe', 100, 'application/x-msdownload');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $proceso),
        ['archivo' => $archivo, 'tipo_documento_id' => $tipoDocumento->id],
    );

    $response->assertSessionHasErrors('archivo');
    expect(Documento::count())->toBe(0);
});

test('un usuario sin el permiso no puede subir ni desvincular y queda auditado como acceso denegado', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $archivo = UploadedFile::fake()->create('bases.pdf', 100, 'application/pdf');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $proceso),
        ['archivo' => $archivo, 'tipo_documento_id' => $tipoDocumento->id],
    );

    $response->assertForbidden();
    expect(Documento::count())->toBe(0);
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('el detalle de un caso de pago de proveedores incluye los documentos vinculados activos', function () {
    $this->withoutVite();
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $archivo = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');

    $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $proceso),
        ['archivo' => $archivo, 'tipo_documento_id' => $tipoDocumento->id],
    );

    expect($proceso->vinculosDocumento()->where('activo', true)->count())->toBe(1);
});

test('descargar un documento vinculado sin autenticacion redirige al login', function () {
    Storage::fake('local');

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'contrato.pdf']);
    $documento->versiones()->create([
        'numero_version' => 1,
        'ruta_archivo' => UploadedFile::fake()->create('contrato.pdf', 10)->store('documentos', 'local'),
        'nombre_archivo' => 'contrato.pdf',
    ]);

    $respuestaSinAuth = $this->get(route('procesos.documentos.descargar', [$proceso, $documento]));
    $respuestaSinAuth->assertRedirect(route('login'));
});

test('descargar un documento vinculado autenticado responde con el archivo', function () {
    Storage::fake('local');

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'contrato.pdf']);
    $documento->versiones()->create([
        'numero_version' => 1,
        'ruta_archivo' => UploadedFile::fake()->create('contrato.pdf', 10)->store('documentos', 'local'),
        'nombre_archivo' => 'contrato.pdf',
    ]);

    $usuario = User::factory()->create();

    $respuestaConAuth = $this->actingAs($usuario)->get(route('procesos.documentos.descargar', [$proceso, $documento]));
    $respuestaConAuth->assertOk();
});

test('desvincular un documento lo marca inactivo sin eliminar el documento ni sus versiones', function () {
    Storage::fake('local');
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $archivo = UploadedFile::fake()->create('garantia.pdf', 100, 'application/pdf');

    $this->actingAs($usuario)->post(
        route('procesos.documentos.store', $proceso),
        ['archivo' => $archivo, 'tipo_documento_id' => $tipoDocumento->id],
    );

    $vinculo = VinculoDocumento::first();

    $response = $this->actingAs($usuario)->delete(route('procesos.documentos.destroy', [$proceso, $vinculo]));

    $response->assertSessionHasNoErrors();
    expect($vinculo->refresh()->activo)->toBeFalse();
    expect(Documento::count())->toBe(1);
    expect($vinculo->documento->versiones)->toHaveCount(1);
});
