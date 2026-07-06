<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearJurisdiccionParaDestroyCfinancieroTest(): Jurisdiccion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    return Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
}

test('un usuario con core_institucional.administrar puede eliminar un centro financiero sin centros de costo', function () {
    $jurisdiccion = crearJurisdiccionParaDestroyCfinancieroTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.cfinancieros.destroy', $cfinanciero));

    $response->assertRedirect(route('maestros.cfinancieros.index'));
    expect(Cfinanciero::find($cfinanciero->id))->toBeNull();
});

test('eliminar un centro financiero con centros de costo asociados es rechazado', function () {
    $jurisdiccion = crearJurisdiccionParaDestroyCfinancieroTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => 'CC1', 'nombre' => 'Costo 1']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.cfinancieros.destroy', $cfinanciero));

    $response->assertRedirect();
    expect(Cfinanciero::find($cfinanciero->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar un centro financiero', function () {
    $jurisdiccion = crearJurisdiccionParaDestroyCfinancieroTest();
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.cfinancieros.destroy', $cfinanciero));

    $response->assertForbidden();
    expect(Cfinanciero::find($cfinanciero->id))->not->toBeNull();
});
