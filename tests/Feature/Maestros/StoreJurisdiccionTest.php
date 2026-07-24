<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearInstitucionParaStoreJurisdiccionTest(): Institucion
{
    return Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);
}

test('un usuario con core_institucional.administrar puede registrar una jurisdicción', function () {
    $institucion = crearInstitucionParaStoreJurisdiccionTest();

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.jurisdicciones.store'), [
        'institucion_id' => $institucion->id,
        'codigo' => '14',
        'nombre' => 'Coyhaique',
    ]);

    $response->assertRedirect(route('maestros.jurisdicciones.index'));

    $jurisdiccion = Jurisdiccion::where('codigo', '14')->first();
    expect($jurisdiccion)->not->toBeNull();
    expect($jurisdiccion->institucion_id)->toBe($institucion->id);
    expect($jurisdiccion->activo)->toBeTrue();
});

test('registrar una jurisdicción con un código ya existente falla la validación', function () {
    $institucion = crearInstitucionParaStoreJurisdiccionTest();
    Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.jurisdicciones.store'), [
        'institucion_id' => $institucion->id,
        'codigo' => '14',
        'nombre' => 'Duplicada',
    ]);

    $response->assertSessionHasErrors('codigo');
    expect(Jurisdiccion::count())->toBe(1);
});

test('registrar una jurisdicción con una institución inexistente falla la validación', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.jurisdicciones.store'), [
        'institucion_id' => 9999,
        'codigo' => '14',
        'nombre' => 'Coyhaique',
    ]);

    $response->assertSessionHasErrors('institucion_id');
    expect(Jurisdiccion::count())->toBe(0);
});

test('un usuario sin core_institucional.administrar no puede registrar una jurisdicción', function () {
    $institucion = crearInstitucionParaStoreJurisdiccionTest();

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.jurisdicciones.store'), [
        'institucion_id' => $institucion->id,
        'codigo' => '14',
        'nombre' => 'Coyhaique',
    ]);

    $response->assertForbidden();
    expect(Jurisdiccion::count())->toBe(0);
});
