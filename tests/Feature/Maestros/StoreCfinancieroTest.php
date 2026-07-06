<?php

use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearJurisdiccionParaStoreCfinancieroTest(): Jurisdiccion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    return Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede registrar un centro financiero', function () {
    $jurisdiccion = crearJurisdiccionParaStoreCfinancieroTest();

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.cfinancieros.store'), [
        'codigo' => '1400',
        'nombre' => 'Administracion Zonal',
        'jurisdiccion_id' => $jurisdiccion->id,
    ]);

    $response->assertRedirect(route('maestros.cfinancieros.index'));

    $cfinanciero = Cfinanciero::where('codigo', '1400')->first();
    expect($cfinanciero)->not->toBeNull();
    expect($cfinanciero->jurisdiccion_id)->toBe($jurisdiccion->id);
    expect($cfinanciero->activo)->toBeTrue();
});

test('registrar un centro financiero con un código ya existente falla la validación', function () {
    $jurisdiccion = crearJurisdiccionParaStoreCfinancieroTest();
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.cfinancieros.store'), [
        'codigo' => '1400',
        'nombre' => 'Otro',
        'jurisdiccion_id' => $jurisdiccion->id,
    ]);

    $response->assertInvalid(['codigo']);
    expect(Cfinanciero::where('codigo', '1400')->count())->toBe(1);
});

test('un usuario sin core_institucional.administrar no puede registrar un centro financiero', function () {
    $jurisdiccion = crearJurisdiccionParaStoreCfinancieroTest();

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.cfinancieros.store'), [
        'codigo' => '1400',
        'nombre' => 'Administracion Zonal',
        'jurisdiccion_id' => $jurisdiccion->id,
    ]);

    $response->assertForbidden();
    expect(Cfinanciero::where('codigo', '1400')->count())->toBe(0);
});
