<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con core_institucional.administrar puede listar centros de costo', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400020301', 'nombre' => 'Corte de Apelaciones de Coyhaique', 'cod_edificio' => 'ED-01']);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.ccostos.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/ccostos/index')
        ->has('ccostos.data', 2)
        ->where('ccostos.data.0.cfinanciero.nombre', 'Administracion Zonal')
    );
});

test('buscar por código devuelve solo las coincidencias', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400020301', 'nombre' => 'Corte de Apelaciones de Coyhaique']);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.ccostos.index', ['q' => '020301']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/ccostos/index')
        ->has('ccostos.data', 1)
        ->where('ccostos.data.0.nombre', 'Corte de Apelaciones de Coyhaique')
    );
});

test('un centro de costo sin código de edificio devuelve el campo en null', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.ccostos.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/ccostos/index')
        ->where('ccostos.data.0.cod_edificio', null)
    );
});

test('un usuario sin core_institucional.administrar no puede listar centros de costo', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.ccostos.index'));

    $response->assertForbidden();
});
