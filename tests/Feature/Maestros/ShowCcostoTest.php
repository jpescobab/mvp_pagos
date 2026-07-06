<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearCfinancieroParaShowCcostoTest(): Cfinanciero
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);

    return Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
}

test('un usuario con core_institucional.administrar puede ver el detalle de un centro de costo', function () {
    $cfinanciero = crearCfinancieroParaShowCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.ccostos.show', $ccosto));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/ccostos/show')
        ->where('ccosto.nombre', 'CAPJ Zonal Coyhaique')
        ->where('ccosto.cfinanciero.id', $cfinanciero->id)
    );
});

test('un usuario sin core_institucional.administrar no puede ver el detalle de un centro de costo', function () {
    $cfinanciero = crearCfinancieroParaShowCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.ccostos.show', $ccosto));

    $response->assertForbidden();
});
