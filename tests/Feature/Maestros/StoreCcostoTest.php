<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearCfinancieroParaStoreCcostoTest(): Cfinanciero
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);

    return Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
}

test('un usuario con core_institucional.administrar puede registrar un centro de costo', function () {
    $cfinanciero = crearCfinancieroParaStoreCcostoTest();

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.ccostos.store'), [
        'codigo' => '1400010201',
        'nombre' => 'CAPJ Zonal Coyhaique',
        'cfinanciero_id' => $cfinanciero->id,
    ]);

    $response->assertRedirect(route('maestros.ccostos.index'));

    $ccosto = Ccosto::where('codigo', '1400010201')->first();
    expect($ccosto)->not->toBeNull();
    expect($ccosto->cfinanciero_id)->toBe($cfinanciero->id);
    expect($ccosto->activo)->toBeTrue();
});

test('registrar un centro de costo con un código ya existente falla la validación', function () {
    $cfinanciero = crearCfinancieroParaStoreCcostoTest();
    Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.ccostos.store'), [
        'codigo' => '1400010201',
        'nombre' => 'Otro',
        'cfinanciero_id' => $cfinanciero->id,
    ]);

    $response->assertInvalid(['codigo']);
    expect(Ccosto::where('codigo', '1400010201')->count())->toBe(1);
});

test('un usuario sin core_institucional.administrar no puede registrar un centro de costo', function () {
    $cfinanciero = crearCfinancieroParaStoreCcostoTest();

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.ccostos.store'), [
        'codigo' => '1400010201',
        'nombre' => 'CAPJ Zonal Coyhaique',
        'cfinanciero_id' => $cfinanciero->id,
    ]);

    $response->assertForbidden();
    expect(Ccosto::where('codigo', '1400010201')->count())->toBe(0);
});
