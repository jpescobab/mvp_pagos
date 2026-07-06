<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearCcostoParaStoreClienteMedidorTest(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    return Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede registrar un cliente medidor', function () {
    $ccosto = crearCcostoParaStoreClienteMedidorTest();

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.clientes-medidores.store'), [
        'numero_cliente' => '111-1',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Eléctrico',
    ]);

    $response->assertRedirect(route('maestros.clientes-medidores.index'));

    $cliente = ClienteMedidor::where('numero_cliente', '111-1')->first();
    expect($cliente)->not->toBeNull();
    expect($cliente->ccosto_id)->toBe($ccosto->id);
    expect($cliente->activo)->toBeTrue();
});

test('registrar un cliente medidor con un número de cliente ya existente falla la validación', function () {
    $ccosto = crearCcostoParaStoreClienteMedidorTest();
    ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.clientes-medidores.store'), [
        'numero_cliente' => '111-1',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Agua',
    ]);

    $response->assertInvalid(['numero_cliente']);
    expect(ClienteMedidor::where('numero_cliente', '111-1')->count())->toBe(1);
});

test('registrar un cliente medidor con un ccosto_id inexistente falla la validación', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.clientes-medidores.store'), [
        'numero_cliente' => '111-1',
        'ccosto_id' => 999999,
        'tipo_suministro' => 'Eléctrico',
    ]);

    $response->assertInvalid(['ccosto_id']);
});

test('un usuario sin core_institucional.administrar no puede registrar un cliente medidor', function () {
    $ccosto = crearCcostoParaStoreClienteMedidorTest();

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.clientes-medidores.store'), [
        'numero_cliente' => '111-1',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Eléctrico',
    ]);

    $response->assertForbidden();
    expect(ClienteMedidor::where('numero_cliente', '111-1')->count())->toBe(0);
});
