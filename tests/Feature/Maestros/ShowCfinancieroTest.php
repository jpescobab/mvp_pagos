<?php

use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearJurisdiccionParaShowCfinancieroTest(): Jurisdiccion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    return Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede ver el detalle de un centro financiero', function () {
    $jurisdiccion = crearJurisdiccionParaShowCfinancieroTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.cfinancieros.show', $cfinanciero));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/cfinancieros/show')
        ->where('cfinanciero.nombre', 'Administracion Zonal')
        ->where('cfinanciero.jurisdiccion.id', $jurisdiccion->id)
    );
});

test('un usuario sin core_institucional.administrar no puede ver el detalle de un centro financiero', function () {
    $jurisdiccion = crearJurisdiccionParaShowCfinancieroTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.cfinancieros.show', $cfinanciero));

    $response->assertForbidden();
});
