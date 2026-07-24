<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede eliminar una institución sin jurisdicciones', function () {
    $institucion = Institucion::create(['codigo' => 'SOLA', 'nombre' => 'Institución Sin Jurisdicciones']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.instituciones.destroy', $institucion));

    $response->assertRedirect(route('maestros.instituciones.index'));
    expect(Institucion::find($institucion->id))->toBeNull();
});

test('eliminar una institución con jurisdicciones asociadas es rechazado', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.instituciones.destroy', $institucion));

    $response->assertRedirect();
    expect(Institucion::find($institucion->id))->not->toBeNull();
    expect(Jurisdiccion::find($jurisdiccion->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar una institución', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.instituciones.destroy', $institucion));

    $response->assertForbidden();
    expect(Institucion::find($institucion->id))->not->toBeNull();
});
