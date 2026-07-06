<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearCfinancieroParaUpdateCcostoTest(): Cfinanciero
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);

    return Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
}

test('un usuario con core_institucional.administrar puede editar un centro de costo', function () {
    $cfinanciero = crearCfinancieroParaUpdateCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.ccostos.update', $ccosto), [
        'codigo' => '1400010201',
        'nombre' => 'CAPJ Zonal Coyhaique Actualizado',
        'cfinanciero_id' => $cfinanciero->id,
        'cod_edificio' => 'EDIF-1',
        'activo' => false,
    ]);

    $response->assertRedirect(route('maestros.ccostos.show', $ccosto));

    $ccosto->refresh();
    expect($ccosto->nombre)->toBe('CAPJ Zonal Coyhaique Actualizado');
    expect($ccosto->cod_edificio)->toBe('EDIF-1');
    expect($ccosto->activo)->toBeFalse();
});

test('editar un centro de costo con el código de otro falla la validación', function () {
    $cfinanciero = crearCfinancieroParaUpdateCcostoTest();
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'Uno']);
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400020301', 'nombre' => 'Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.ccostos.update', $ccosto), [
        'codigo' => '1400010201',
        'nombre' => 'Dos',
        'cfinanciero_id' => $cfinanciero->id,
    ]);

    $response->assertInvalid(['codigo']);
    expect($ccosto->refresh()->codigo)->toBe('1400020301');
});

test('un usuario sin core_institucional.administrar no puede editar un centro de costo', function () {
    $cfinanciero = crearCfinancieroParaUpdateCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);

    $actor = User::factory()->create();

    $responseGet = $this->actingAs($actor)->get(route('maestros.ccostos.edit', $ccosto));
    $responseGet->assertForbidden();

    $responsePatch = $this->actingAs($actor)->patch(route('maestros.ccostos.update', $ccosto), [
        'codigo' => '1400010201',
        'nombre' => 'Otro nombre',
        'cfinanciero_id' => $cfinanciero->id,
    ]);
    $responsePatch->assertForbidden();
    expect($ccosto->refresh()->nombre)->toBe('CAPJ Zonal Coyhaique');
});
