<?php

use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearJurisdiccionParaShowJurisdiccionTest(): Jurisdiccion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);

    return Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);
}

test('un usuario con core_institucional.administrar ve el detalle de una jurisdicción con sus centros financieros', function () {
    $jurisdiccion = crearJurisdiccionParaShowJurisdiccionTest();
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1401', 'nombre' => 'Garantía']);
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.jurisdicciones.show', $jurisdiccion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/jurisdicciones/show')
        ->where('jurisdiccion.codigo', '14')
        ->where('jurisdiccion.institucion.codigo', 'CAPJ')
        ->has('jurisdiccion.cfinancieros', 2)
        ->where('jurisdiccion.cfinancieros.0.codigo', '1400')
        ->where('jurisdiccion.cfinancieros.1.codigo', '1401')
    );
});

test('el detalle de una jurisdicción sin centros financieros se muestra con la lista vacía', function () {
    $jurisdiccion = crearJurisdiccionParaShowJurisdiccionTest();

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.jurisdicciones.show', $jurisdiccion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/jurisdicciones/show')
        ->where('jurisdiccion.nombre', 'Coyhaique')
        ->has('jurisdiccion.cfinancieros', 0)
    );
});

test('un usuario sin core_institucional.administrar no puede ver el detalle de una jurisdicción', function () {
    $jurisdiccion = crearJurisdiccionParaShowJurisdiccionTest();

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.jurisdicciones.show', $jurisdiccion));

    $response->assertForbidden();
});
