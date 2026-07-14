<?php

use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\Documento;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

test('un usuario con core_institucional.administrar puede crear un tipo de documento', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->post(route('maestros.tipos-documento.store'), [
        'codigo' => 'FURBS',
        'nombre' => 'FURBS',
    ]);

    $response->assertRedirect(route('maestros.tipos-documento.index'));

    $tipo = TipoDocumento::where('codigo', 'FURBS')->first();
    expect($tipo)->not->toBeNull();
    expect($tipo->activo)->toBeTrue();
});

test('eliminar un tipo de documento con documentos vinculados es rechazado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $tipo = TipoDocumento::create(['codigo' => 'FURBS', 'nombre' => 'FURBS']);
    Documento::create(['tipo_documento_id' => $tipo->id, 'titulo' => 'furbs.pdf']);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->delete(route('maestros.tipos-documento.destroy', $tipo));

    $response->assertRedirect();
    expect(TipoDocumento::find($tipo->id))->not->toBeNull();
});

test('eliminar un tipo de documento con requisitos documentales asociados es rechazado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $tipo = TipoDocumento::create(['codigo' => 'FURBS', 'nombre' => 'FURBS']);
    $conjunto = ConjuntoRequisitosDocumentales::firstOrCreate(['codigo' => 'pago_proveedores'], ['nombre' => 'Pago de Proveedores', 'activo' => true]);
    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();

    RequisitoDocumental::create([
        'conjunto_requisitos_documentales_id' => $conjunto->id,
        'tipo_documento_id' => $tipo->id,
        'definicion_workflow_id' => $definicion->id,
        'tipo_requisito' => 'opcional',
        'activo' => true,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->delete(route('maestros.tipos-documento.destroy', $tipo));

    $response->assertRedirect();
    expect(TipoDocumento::find($tipo->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede administrar tipos de documento', function () {
    $tipo = TipoDocumento::create(['codigo' => 'FURBS', 'nombre' => 'FURBS']);

    $usuario = User::factory()->create();

    $this->actingAs($usuario)->post(route('maestros.tipos-documento.store'), [
        'codigo' => 'NUEVO',
        'nombre' => 'Nuevo',
    ])->assertForbidden();

    $this->actingAs($usuario)->delete(route('maestros.tipos-documento.destroy', $tipo))
        ->assertForbidden();

    expect(TipoDocumento::find($tipo->id))->not->toBeNull();
});
