<?php

use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\Funcionario;
use App\Models\Proceso;
use App\Models\TipoDocumento;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearProcesoDePruebaParaReclasificar(): Proceso
{
    $sufijo = fake()->unique()->numerify('####');

    $definicion = DefinicionWorkflow::create(['codigo' => "wf-reclasificar-{$sufijo}", 'nombre' => 'Workflow reclasificar']);
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

function vincularDocumentoDePrueba(Proceso $proceso, string $codigoTipo = 'OTRO_TEST'): Documento
{
    $tipo = TipoDocumento::firstOrCreate(['codigo' => $codigoTipo], ['nombre' => $codigoTipo, 'activo' => true]);
    $documento = Documento::create(['tipo_documento_id' => $tipo->id, 'titulo' => 'doc-'.fake()->unique()->numerify('####')]);

    $proceso->vinculosDocumento()->create(['documento_id' => $documento->id, 'activo' => true]);

    return $documento;
}

test('reclasificar un documento vinculado con el permiso requerido actualiza su tipo_documento_id', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $proceso = crearProcesoDePruebaParaReclasificar();
    $documento = vincularDocumentoDePrueba($proceso);
    $tipoDestino = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura', 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $response = $this->actingAs($usuario)->patch(
        route('procesos.documentos.tipo-documento.store', [$proceso, $documento]),
        ['tipo_documento_id' => $tipoDestino->id],
    );

    $response->assertSessionHasNoErrors();
    expect($documento->refresh()->tipo_documento_id)->toBe($tipoDestino->id);
});

test('un usuario sin documentos.gestionar no puede reclasificar', function () {
    $proceso = crearProcesoDePruebaParaReclasificar();
    $documento = vincularDocumentoDePrueba($proceso);
    $tipoDestino = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura', 'activo' => true]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->patch(
        route('procesos.documentos.tipo-documento.store', [$proceso, $documento]),
        ['tipo_documento_id' => $tipoDestino->id],
    );

    $response->assertForbidden();
    expect($documento->refresh()->tipo_documento_id)->not->toBe($tipoDestino->id);
});

test('reclasificar un documento sin vínculo activo con el proceso indicado es rechazado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $procesoReal = crearProcesoDePruebaParaReclasificar();
    $procesoAjeno = crearProcesoDePruebaParaReclasificar();
    $documento = vincularDocumentoDePrueba($procesoReal);
    $tipoDestino = TipoDocumento::firstOrCreate(['codigo' => 'FACTURA'], ['nombre' => 'Factura', 'activo' => true]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('documentos.gestionar');

    $response = $this->actingAs($usuario)->patch(
        route('procesos.documentos.tipo-documento.store', [$procesoAjeno, $documento]),
        ['tipo_documento_id' => $tipoDestino->id],
    );

    $response->assertNotFound();
    expect($documento->refresh()->tipo_documento_id)->not->toBe($tipoDestino->id);
});
