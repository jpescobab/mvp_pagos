<?php

use App\Models\Institucion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede registrar una institución', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.instituciones.store'), [
        'codigo' => 'CAPJ',
        'nombre' => 'Corporación Administrativa del Poder Judicial',
    ]);

    $response->assertRedirect(route('maestros.instituciones.index'));

    $institucion = Institucion::where('codigo', 'CAPJ')->first();
    expect($institucion)->not->toBeNull();
    expect($institucion->nombre)->toBe('Corporación Administrativa del Poder Judicial');
    expect($institucion->activo)->toBeTrue();
});

test('registrar una institución con un código ya existente falla la validación', function () {
    Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.instituciones.store'), [
        'codigo' => 'CAPJ',
        'nombre' => 'Duplicada',
    ]);

    $response->assertSessionHasErrors('codigo');
    expect(Institucion::count())->toBe(1);
});

test('un usuario sin core_institucional.administrar no puede registrar una institución', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.instituciones.store'), [
        'codigo' => 'CAPJ',
        'nombre' => 'Corporación Administrativa del Poder Judicial',
    ]);

    $response->assertForbidden();
    expect(Institucion::count())->toBe(0);
});
