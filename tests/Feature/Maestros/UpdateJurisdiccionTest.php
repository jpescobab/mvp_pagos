<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearInstitucionParaUpdateJurisdiccionTest(): Institucion
{
    return Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);
}

test('un usuario con core_institucional.administrar puede editar una jurisdicción', function () {
    $institucion = crearInstitucionParaUpdateJurisdiccionTest();
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Nombre Viejo']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.jurisdicciones.update', $jurisdiccion), [
        'institucion_id' => $institucion->id,
        'codigo' => '14',
        'nombre' => 'Coyhaique',
        'descripcion' => 'Zonal austral',
        'activo' => false,
    ]);

    $response->assertRedirect(route('maestros.jurisdicciones.show', $jurisdiccion));

    $jurisdiccion->refresh();
    expect($jurisdiccion->nombre)->toBe('Coyhaique');
    expect($jurisdiccion->descripcion)->toBe('Zonal austral');
    expect($jurisdiccion->activo)->toBeFalse();
});

test('guardar una jurisdicción sin cambiar su código no la reporta como duplicada consigo misma', function () {
    $institucion = crearInstitucionParaUpdateJurisdiccionTest();
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Nombre Viejo']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.jurisdicciones.update', $jurisdiccion), [
        'institucion_id' => $institucion->id,
        'codigo' => '14',
        'nombre' => 'Nombre Corregido',
    ]);

    $response->assertSessionHasNoErrors();
    expect($jurisdiccion->refresh()->nombre)->toBe('Nombre Corregido');
});

test('una jurisdicción puede reasignarse a otra institución', function () {
    $institucion = crearInstitucionParaUpdateJurisdiccionTest();
    $otra = Institucion::create(['codigo' => 'OTRA', 'nombre' => 'Otra Institución']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.jurisdicciones.update', $jurisdiccion), [
        'institucion_id' => $otra->id,
        'codigo' => '14',
        'nombre' => 'Coyhaique',
    ]);

    $response->assertSessionHasNoErrors();
    expect($jurisdiccion->refresh()->institucion_id)->toBe($otra->id);
});

test('un usuario sin core_institucional.administrar no puede editar una jurisdicción', function () {
    $institucion = crearInstitucionParaUpdateJurisdiccionTest();
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Nombre Viejo']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('maestros.jurisdicciones.update', $jurisdiccion), [
        'institucion_id' => $institucion->id,
        'codigo' => '14',
        'nombre' => 'Nombre Nuevo',
    ]);

    $response->assertForbidden();
    expect($jurisdiccion->refresh()->nombre)->toBe('Nombre Viejo');
});
