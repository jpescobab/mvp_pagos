<?php

use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearJurisdiccionParaDestroyJurisdiccionTest(): Jurisdiccion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Corporación Administrativa del Poder Judicial']);

    return Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);
}

test('un usuario con core_institucional.administrar puede eliminar una jurisdicción sin centros financieros', function () {
    $jurisdiccion = crearJurisdiccionParaDestroyJurisdiccionTest();

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.jurisdicciones.destroy', $jurisdiccion));

    $response->assertRedirect(route('maestros.jurisdicciones.index'));
    expect(Jurisdiccion::find($jurisdiccion->id))->toBeNull();
});

test('eliminar una jurisdicción con centros financieros asociados es rechazado', function () {
    $jurisdiccion = crearJurisdiccionParaDestroyJurisdiccionTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.jurisdicciones.destroy', $jurisdiccion));

    $response->assertRedirect();
    expect(Jurisdiccion::find($jurisdiccion->id))->not->toBeNull();
    expect(Cfinanciero::find($cfinanciero->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar una jurisdicción', function () {
    $jurisdiccion = crearJurisdiccionParaDestroyJurisdiccionTest();

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.jurisdicciones.destroy', $jurisdiccion));

    $response->assertForbidden();
    expect(Jurisdiccion::find($jurisdiccion->id))->not->toBeNull();
});
