<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearInstitucionParaConsultarJurisdiccionesTest(): Institucion
{
    return Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);
}

test('un usuario con core_institucional.administrar puede listar jurisdicciones', function () {
    $institucion = crearInstitucionParaConsultarJurisdiccionesTest();
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '01', 'nombre' => 'Arica', 'descripcion' => 'Zonal norte']);
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.jurisdicciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/jurisdicciones/index')
        ->has('jurisdicciones.data', 2)
        ->where('jurisdicciones.data.0.codigo', '01')
        ->where('jurisdicciones.data.0.institucion.nombre', 'Corporación Administrativa del Poder Judicial')
        ->where('jurisdicciones.data.0.descripcion', 'Zonal norte')
    );
});

test('buscar jurisdicciones por nombre devuelve solo las coincidencias', function () {
    $institucion = crearInstitucionParaConsultarJurisdiccionesTest();
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '01', 'nombre' => 'Arica']);
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.jurisdicciones.index', ['q' => 'Coyhaique']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/jurisdicciones/index')
        ->has('jurisdicciones.data', 1)
        ->where('jurisdicciones.data.0.codigo', '14')
    );
});

test('una jurisdicción sin descripción se lista con la descripción nula', function () {
    $institucion = crearInstitucionParaConsultarJurisdiccionesTest();
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.jurisdicciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/jurisdicciones/index')
        ->has('jurisdicciones.data', 1)
        ->where('jurisdicciones.data.0.descripcion', null)
    );
});

test('un usuario sin core_institucional.administrar no puede listar jurisdicciones', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.jurisdicciones.index'));

    $response->assertForbidden();
});
