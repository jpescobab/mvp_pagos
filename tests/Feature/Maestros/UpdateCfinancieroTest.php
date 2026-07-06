<?php

use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearJurisdiccionParaUpdateCfinancieroTest(): Jurisdiccion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    return Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede editar un centro financiero', function () {
    $jurisdiccion = crearJurisdiccionParaUpdateCfinancieroTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.cfinancieros.update', $cfinanciero), [
        'codigo' => '1400',
        'nombre' => 'Administracion Zonal Actualizada',
        'jurisdiccion_id' => $jurisdiccion->id,
        'activo' => false,
    ]);

    $response->assertRedirect(route('maestros.cfinancieros.show', $cfinanciero));

    $cfinanciero->refresh();
    expect($cfinanciero->nombre)->toBe('Administracion Zonal Actualizada');
    expect($cfinanciero->activo)->toBeFalse();
});

test('editar un centro financiero con el código de otro falla la validación', function () {
    $jurisdiccion = crearJurisdiccionParaUpdateCfinancieroTest();
    Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Uno']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1401', 'nombre' => 'Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.cfinancieros.update', $cfinanciero), [
        'codigo' => '1400',
        'nombre' => 'Dos',
        'jurisdiccion_id' => $jurisdiccion->id,
    ]);

    $response->assertInvalid(['codigo']);
    expect($cfinanciero->refresh()->codigo)->toBe('1401');
});

test('un usuario sin core_institucional.administrar no puede editar un centro financiero', function () {
    $jurisdiccion = crearJurisdiccionParaUpdateCfinancieroTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $actor = User::factory()->create();

    $responseGet = $this->actingAs($actor)->get(route('maestros.cfinancieros.edit', $cfinanciero));
    $responseGet->assertForbidden();

    $responsePatch = $this->actingAs($actor)->patch(route('maestros.cfinancieros.update', $cfinanciero), [
        'codigo' => '1400',
        'nombre' => 'Otro nombre',
        'jurisdiccion_id' => $jurisdiccion->id,
    ]);
    $responsePatch->assertForbidden();
    expect($cfinanciero->refresh()->nombre)->toBe('Administracion Zonal');
});
