<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearCcostoParaDestroyClienteMedidorTest(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    return Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede eliminar un cliente medidor', function () {
    $ccosto = crearCcostoParaDestroyClienteMedidorTest();
    $cliente = ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.clientes-medidores.destroy', $cliente));

    $response->assertRedirect(route('maestros.clientes-medidores.index'));
    expect(ClienteMedidor::find($cliente->id))->toBeNull();
    expect(ClienteMedidor::withTrashed()->find($cliente->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar un cliente medidor', function () {
    $ccosto = crearCcostoParaDestroyClienteMedidorTest();
    $cliente = ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.clientes-medidores.destroy', $cliente));

    $response->assertForbidden();
    expect(ClienteMedidor::find($cliente->id))->not->toBeNull();
});
