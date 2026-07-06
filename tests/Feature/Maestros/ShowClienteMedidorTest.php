<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearCcostoParaShowClienteMedidorTest(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    return Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede ver el detalle de un cliente medidor', function () {
    $ccosto = crearCcostoParaShowClienteMedidorTest();
    $cliente = ClienteMedidor::create([
        'numero_cliente' => '111-1',
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Eléctrico',
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.clientes-medidores.show', $cliente));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/clientes-medidores/show')
        ->where('clienteMedidor.numero_cliente', '111-1')
        ->where('clienteMedidor.ccosto.id', $ccosto->id)
    );
});

test('un usuario sin core_institucional.administrar no puede ver el detalle de un cliente medidor', function () {
    $ccosto = crearCcostoParaShowClienteMedidorTest();
    $cliente = ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.clientes-medidores.show', $cliente));

    $response->assertForbidden();
});
