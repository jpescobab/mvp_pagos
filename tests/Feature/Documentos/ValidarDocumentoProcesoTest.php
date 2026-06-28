<?php

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\Documento;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Services\Documentos\ResolutorChecklistDocumentalProceso;
use Database\Seeders\RolesAndPermissionsSeeder;

test('validar un documento con el permiso requerido crea el evento y actualiza su estado vigente', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.validar');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.validaciones.store', [$proceso, $documento]),
        ['estado' => 'valido'],
    );

    $response->assertSessionHasNoErrors();
    expect($documento->refresh()->estadoVigente())->toBe('valido');
    expect($documento->validaciones->first()->validado_por)->toBe($usuario->id);
});

test('rechazar un documento sin observacion es rechazado por validacion y no crea ningun evento', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.validar');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.validaciones.store', [$proceso, $documento]),
        ['estado' => 'rechazado'],
    );

    $response->assertSessionHasErrors('observacion');
    expect($documento->validaciones)->toHaveCount(0);
});

test('rechazar un documento con observacion crea el evento y actualiza su estado vigente', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.validar');

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.validaciones.store', [$proceso, $documento]),
        ['estado' => 'rechazado', 'observacion' => 'Falta firma del representante legal'],
    );

    $response->assertSessionHasNoErrors();
    expect($documento->refresh()->estadoVigente())->toBe('rechazado');
    expect($documento->validaciones->first()->observacion)->toBe('Falta firma del representante legal');
});

test('un usuario sin el permiso no puede validar ni rechazar y queda auditado como acceso denegado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('procesos.documentos.validaciones.store', [$proceso, $documento]),
        ['estado' => 'valido'],
    );

    $response->assertForbidden();
    expect($documento->validaciones)->toHaveCount(0);
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('tras validar un documento, la siguiente resolucion del checklist refleja el nuevo estado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaDocumentos();
    $tipoDocumento = crearTipoDocumentoDePrueba();
    $conjunto = ConjuntoRequisitosDocumentales::create(['codigo' => 'set-val-'.fake()->unique()->numerify('####'), 'nombre' => 'Set de prueba']);
    $conjunto->requisitos()->create([
        'tipo_documento_id' => $tipoDocumento->id,
        'definicion_workflow_id' => $proceso->definicion_workflow_id,
        'tipo_requisito' => 'requerido',
    ]);

    $documento = Documento::create(['tipo_documento_id' => $tipoDocumento->id, 'titulo' => 'doc.pdf']);
    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    $resolutor = app(ResolutorChecklistDocumentalProceso::class);
    $checklistAntes = $resolutor->resolve($proceso, $conjunto);
    expect($checklistAntes->items->first()->estado_cumplimiento)->toBe('cargado');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.validar');

    $this->actingAs($usuario)->post(
        route('procesos.documentos.validaciones.store', [$proceso, $documento]),
        ['estado' => 'valido'],
    );

    $checklistDespues = $resolutor->resolve($proceso, $conjunto);
    expect($checklistDespues->items->first()->estado_cumplimiento)->toBe('valido');
});
