<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con core_institucional.administrar ve el detalle de una institución con sus jurisdicciones', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '01', 'nombre' => 'Arica']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.instituciones.show', $institucion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/instituciones/show')
        ->where('institucion.codigo', 'CAPJ')
        ->has('institucion.jurisdicciones', 2)
        ->where('institucion.jurisdicciones.0.codigo', '01')
        ->where('institucion.jurisdicciones.1.codigo', '14')
    );
});

test('el detalle de una institución sin jurisdicciones se muestra con la lista vacía', function () {
    $institucion = Institucion::create(['codigo' => 'SOLA', 'nombre' => 'Institución Sin Jurisdicciones']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.instituciones.show', $institucion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/instituciones/show')
        ->where('institucion.nombre', 'Institución Sin Jurisdicciones')
        ->has('institucion.jurisdicciones', 0)
    );
});

test('un usuario sin core_institucional.administrar no puede ver el detalle de una institución', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.instituciones.show', $institucion));

    $response->assertForbidden();
});
