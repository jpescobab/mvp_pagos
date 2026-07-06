<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearCfinancieroParaDestroyCcostoTest(): Cfinanciero
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);

    return Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);
}

test('un usuario con core_institucional.administrar puede eliminar un centro de costo sin relaciones', function () {
    $cfinanciero = crearCfinancieroParaDestroyCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.ccostos.destroy', $ccosto));

    $response->assertRedirect(route('maestros.ccostos.index'));
    expect(Ccosto::find($ccosto->id))->toBeNull();
});

test('eliminar un centro de costo con clientes medidores asociados es rechazado', function () {
    $cfinanciero = crearCfinancieroParaDestroyCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
    ClienteMedidor::create(['numero_cliente' => '111-1', 'ccosto_id' => $ccosto->id, 'tipo_suministro' => 'Eléctrico']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.ccostos.destroy', $ccosto));

    $response->assertRedirect();
    expect(Ccosto::find($ccosto->id))->not->toBeNull();
});

test('eliminar un centro de costo con procesos de adquisición asociados es rechazado', function () {
    $cfinanciero = crearCfinancieroParaDestroyCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);
    $modalidad = ModalidadAdquisicion::create(['codigo' => 'MOD-1', 'nombre' => 'Trato directo']);
    ProcesoAdquisicion::create([
        'codigo' => 'PA-1',
        'modalidad_id' => $modalidad->id,
        'ccosto_id' => $ccosto->id,
        'objeto' => 'Compra de insumos',
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.ccostos.destroy', $ccosto));

    $response->assertRedirect();
    expect(Ccosto::find($ccosto->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar un centro de costo', function () {
    $cfinanciero = crearCfinancieroParaDestroyCcostoTest();
    $ccosto = Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => '1400010201', 'nombre' => 'CAPJ Zonal Coyhaique']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.ccostos.destroy', $ccosto));

    $response->assertForbidden();
    expect(Ccosto::find($ccosto->id))->not->toBeNull();
});
