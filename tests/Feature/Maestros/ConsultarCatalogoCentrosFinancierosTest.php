<?php

use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con core_institucional.administrar puede listar centros financieros', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1401', 'nombre' => 'Garantía']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.cfinancieros.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/cfinancieros/index')
        ->has('cfinancieros.data', 2)
        ->where('cfinancieros.data.0.jurisdiccion.nombre', 'Zonal Coyhaique')
    );
});

test('buscar por código devuelve solo las coincidencias', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1401', 'nombre' => 'Garantía']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.cfinancieros.index', ['q' => '1401']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/cfinancieros/index')
        ->has('cfinancieros.data', 1)
        ->where('cfinancieros.data.0.nombre', 'Garantía')
    );
});

test('un usuario sin core_institucional.administrar no puede listar centros financieros', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.cfinancieros.index'));

    $response->assertForbidden();
});
