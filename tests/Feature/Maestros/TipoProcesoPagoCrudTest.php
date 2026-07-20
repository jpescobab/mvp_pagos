<?php

use App\Models\CasoPagoProveedor;
use App\Models\ConjuntoRequisitosDocumentales;
use App\Models\DefinicionWorkflow;
use App\Models\Proceso;
use App\Models\RequisitoDocumental;
use App\Models\TipoDocumento;
use App\Models\TipoProcesoPago;
use App\Models\User;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function sembrarPermisoAdministrarRequisitosDocumentales(): void
{
    test()->seed(WorkflowPagoProveedoresSeeder::class);
}

test('un usuario con el permiso puede crear un tipo de proceso de pago', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    $response = $this->actingAs($usuario)->post(route('maestros.tipos-proceso-pago.store'), [
        'codigo' => 'CONSUMOS_BASICOS',
        'nombre' => 'Consumos básicos',
    ]);

    $response->assertRedirect(route('maestros.tipos-proceso-pago.index'));

    $tipo = TipoProcesoPago::where('codigo', 'CONSUMOS_BASICOS')->first();
    expect($tipo)->not->toBeNull();
    expect($tipo->activo)->toBeTrue();
});

test('un tipo de proceso de pago nuevo se crea con requiere_traspaso_cgu en true por defecto', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    $response = $this->actingAs($usuario)->post(route('maestros.tipos-proceso-pago.store'), [
        'codigo' => 'SIN_TRASPASO_DEFAULT',
        'nombre' => 'Sin traspaso default',
    ]);

    $response->assertSessionHasNoErrors();

    $tipo = TipoProcesoPago::where('codigo', 'SIN_TRASPASO_DEFAULT')->first();
    expect($tipo->requiere_traspaso_cgu)->toBeTrue();
});

test('un usuario con permiso puede crear un tipo de proceso de pago que no requiere traspaso CGU', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    $response = $this->actingAs($usuario)->post(route('maestros.tipos-proceso-pago.store'), [
        'codigo' => 'SIN_TRASPASO',
        'nombre' => 'Sin traspaso',
        'requiere_traspaso_cgu' => false,
    ]);

    $response->assertSessionHasNoErrors();

    $tipo = TipoProcesoPago::where('codigo', 'SIN_TRASPASO')->first();
    expect($tipo->requiere_traspaso_cgu)->toBeFalse();
});

test('editar un tipo de proceso de pago puede desmarcar requiere_traspaso_cgu sin afectar código ni nombre', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();
    $tipo = TipoProcesoPago::create(['codigo' => 'COMPRA', 'nombre' => 'Compra']);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    $response = $this->actingAs($usuario)->patch(route('maestros.tipos-proceso-pago.update', $tipo), [
        'codigo' => $tipo->codigo,
        'nombre' => $tipo->nombre,
        'requiere_traspaso_cgu' => false,
    ]);

    $response->assertSessionHasNoErrors();
    $tipo->refresh();
    expect($tipo->requiere_traspaso_cgu)->toBeFalse();
    expect($tipo->codigo)->toBe('COMPRA');
    expect($tipo->nombre)->toBe('Compra');
});

test('crear un tipo de proceso de pago con un código ya existente (sin distinguir mayúsculas) falla la validación', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();
    TipoProcesoPago::create(['codigo' => 'COMPRA', 'nombre' => 'Compra']);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    $response = $this->actingAs($usuario)->post(route('maestros.tipos-proceso-pago.store'), [
        'codigo' => 'compra',
        'nombre' => 'Otro nombre',
    ]);

    $response->assertInvalid(['codigo']);
    expect(TipoProcesoPago::where('codigo', 'COMPRA')->count())->toBe(1);
});

test('desactivar un tipo de proceso de pago no afecta los casos que ya lo tienen asignado', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();
    $tipo = TipoProcesoPago::create(['codigo' => 'COMPRA', 'nombre' => 'Compra']);

    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
    $caso = CasoPagoProveedor::create(['sgf_id' => 'sgf-tipo-proceso-1', 'monto' => 1000]);
    $proceso = Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $definicion->estados()->where('es_inicial', true)->value('id'),
        'sujeto_type' => CasoPagoProveedor::class,
        'sujeto_id' => $caso->id,
        'monto' => 1000,
        'tipo_proceso_pago_id' => $tipo->id,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    $response = $this->actingAs($usuario)->patch(route('maestros.tipos-proceso-pago.update', $tipo), [
        'codigo' => $tipo->codigo,
        'nombre' => $tipo->nombre,
        'activo' => false,
    ]);

    $response->assertSessionHasNoErrors();
    expect($tipo->refresh()->activo)->toBeFalse();
    expect($proceso->refresh()->tipo_proceso_pago_id)->toBe($tipo->id);
});

test('eliminar un tipo de proceso de pago con requisitos documentales asociados es rechazado', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();
    $this->seed(TiposDocumentoSeeder::class);

    $tipo = TipoProcesoPago::create(['codigo' => 'COMPRA', 'nombre' => 'Compra']);
    $conjunto = ConjuntoRequisitosDocumentales::firstOrCreate(['codigo' => 'pago_proveedores'], ['nombre' => 'Pago de Proveedores', 'activo' => true]);
    $definicion = DefinicionWorkflow::where('codigo', 'pago_proveedores')->firstOrFail();
    $tipoDocumento = TipoDocumento::where('codigo', 'FACTURA')->firstOrFail();

    RequisitoDocumental::create([
        'conjunto_requisitos_documentales_id' => $conjunto->id,
        'tipo_documento_id' => $tipoDocumento->id,
        'definicion_workflow_id' => $definicion->id,
        'tipo_proceso_pago_id' => $tipo->id,
        'tipo_requisito' => 'obligatorio',
        'activo' => true,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.administrar_requisitos_documentales');

    $response = $this->actingAs($usuario)->delete(route('maestros.tipos-proceso-pago.destroy', $tipo));

    $response->assertRedirect();
    expect(TipoProcesoPago::find($tipo->id))->not->toBeNull();
});

test('un usuario sin el permiso no puede administrar tipos de proceso de pago', function () {
    sembrarPermisoAdministrarRequisitosDocumentales();
    $tipo = TipoProcesoPago::create(['codigo' => 'COMPRA', 'nombre' => 'Compra']);

    $usuario = User::factory()->create();

    $this->actingAs($usuario)->post(route('maestros.tipos-proceso-pago.store'), [
        'codigo' => 'NUEVO',
        'nombre' => 'Nuevo',
    ])->assertForbidden();

    $this->actingAs($usuario)->patch(route('maestros.tipos-proceso-pago.update', $tipo), [
        'codigo' => $tipo->codigo,
        'nombre' => 'Cambiado',
    ])->assertForbidden();

    $this->actingAs($usuario)->delete(route('maestros.tipos-proceso-pago.destroy', $tipo))
        ->assertForbidden();

    expect(TipoProcesoPago::find($tipo->id)->nombre)->toBe('Compra');
});
