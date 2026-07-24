<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con core_institucional.administrar puede listar instituciones', function () {
    $capj = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);
    Jurisdiccion::create(['institucion_id' => $capj->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);
    Institucion::create(['codigo' => 'OTRA', 'nombre' => 'Otra Institución']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.instituciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/instituciones/index')
        ->has('instituciones.data', 2)
        ->where('instituciones.data.0.codigo', 'CAPJ')
        ->where('instituciones.data.0.jurisdicciones_count', 1)
        ->where('instituciones.data.0.activo', true)
        ->where('instituciones.data.1.jurisdicciones_count', 0)
    );
});

test('buscar instituciones por código devuelve solo las coincidencias', function () {
    Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);
    Institucion::create(['codigo' => 'OTRA', 'nombre' => 'Otra Institución']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.instituciones.index', ['q' => 'OTRA']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/instituciones/index')
        ->has('instituciones.data', 1)
        ->where('instituciones.data.0.nombre', 'Otra Institución')
    );
});

test('buscar instituciones por nombre devuelve solo las coincidencias', function () {
    Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);
    Institucion::create(['codigo' => 'OTRA', 'nombre' => 'Otra Institución']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.instituciones.index', ['q' => 'Poder Judicial']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/instituciones/index')
        ->has('instituciones.data', 1)
        ->where('instituciones.data.0.codigo', 'CAPJ')
    );
});

test('un usuario sin core_institucional.administrar no puede listar instituciones', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.instituciones.index'));

    $response->assertForbidden();
});
