<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearCcostoParaUpdateClienteMedidorTest(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    return Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede editar un cliente medidor', function () {
    $ccosto = crearCcostoParaUpdateClienteMedidorTest();
    $cliente = ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.clientes-medidores.update', $cliente), [
        'numero_cliente' => '111-1',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Agua',
        'activo' => false,
    ]);

    $response->assertRedirect(route('maestros.clientes-medidores.show', $cliente));

    $cliente->refresh();
    expect($cliente->tipo_suministro)->toBe('Agua');
    expect($cliente->activo)->toBeFalse();
});

test('editar un cliente medidor con el número de cliente de otro falla la validación', function () {
    $ccosto = crearCcostoParaUpdateClienteMedidorTest();
    ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);
    $cliente = ClienteMedidor::create(['numero_cliente' => '222-2', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Agua']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.clientes-medidores.update', $cliente), [
        'numero_cliente' => '111-1',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Agua',
    ]);

    $response->assertInvalid(['numero_cliente']);
    expect($cliente->refresh()->numero_cliente)->toBe('222-2');
});

test('un usuario sin core_institucional.administrar no puede editar un cliente medidor', function () {
    $ccosto = crearCcostoParaUpdateClienteMedidorTest();
    $cliente = ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $actor = User::factory()->create();

    $responseGet = $this->actingAs($actor)->get(route('maestros.clientes-medidores.edit', $cliente));
    $responseGet->assertForbidden();

    $responsePatch = $this->actingAs($actor)->patch(route('maestros.clientes-medidores.update', $cliente), [
        'numero_cliente' => '111-1',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Otro',
    ]);
    $responsePatch->assertForbidden();
    expect($cliente->refresh()->tipo_suministro)->toBe('Eléctrico');
});
