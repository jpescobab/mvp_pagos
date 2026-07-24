<?php

use App\Models\Institucion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede editar una institución', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Nombre Viejo']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.instituciones.update', $institucion), [
        'codigo' => 'CAPJ',
        'nombre' => 'Nombre Nuevo',
        'activo' => false,
    ]);

    $response->assertRedirect(route('maestros.instituciones.show', $institucion));

    $institucion->refresh();
    expect($institucion->nombre)->toBe('Nombre Nuevo');
    expect($institucion->activo)->toBeFalse();
});

test('guardar una institución sin cambiar su código no la reporta como duplicada consigo misma', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Nombre Viejo']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.instituciones.update', $institucion), [
        'codigo' => 'CAPJ',
        'nombre' => 'Nombre Corregido',
    ]);

    $response->assertSessionHasNoErrors();
    expect($institucion->refresh()->nombre)->toBe('Nombre Corregido');
});

test('editar una institución con el código de otra falla la validación', function () {
    Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Primera']);
    $otra = Institucion::create(['codigo' => 'OTRA', 'nombre' => 'Segunda']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.instituciones.update', $otra), [
        'codigo' => 'CAPJ',
        'nombre' => 'Segunda',
    ]);

    $response->assertSessionHasErrors('codigo');
    expect($otra->refresh()->codigo)->toBe('OTRA');
});

test('un usuario sin core_institucional.administrar no puede editar una institución', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Nombre Viejo']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('maestros.instituciones.update', $institucion), [
        'codigo' => 'CAPJ',
        'nombre' => 'Nombre Nuevo',
    ]);

    $response->assertForbidden();
    expect($institucion->refresh()->nombre)->toBe('Nombre Viejo');
});
