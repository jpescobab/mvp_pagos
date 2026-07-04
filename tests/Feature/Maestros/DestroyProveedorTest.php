<?php

use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\Cfinanciero;
use App\Models\ClienteMedidor;
use App\Models\Factura;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\ModalidadAdquisicion;
use App\Models\ProcesoAdquisicion;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function crearCcostoParaDestroyProveedorTest(): Ccosto
{
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => '1400', 'nombre' => 'Administracion Zonal']);

    return Ccosto::create(['cfinanciero_id' => $cfinanciero->id, 'codigo' => 'CC1', 'nombre' => 'Costo 1']);
}

test('un usuario con core_institucional.administrar puede eliminar un proveedor sin relaciones', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.proveedores.destroy', $proveedor));

    $response->assertRedirect(route('maestros.proveedores.index'));
    expect(Proveedor::find($proveedor->id))->toBeNull();
    expect(Proveedor::withTrashed()->find($proveedor->id))->not->toBeNull();
});

test('eliminar un proveedor con clientes medidores asociados es rechazado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);
    $ccosto = crearCcostoParaDestroyProveedorTest();
    ClienteMedidor::create([
        'numero_cliente' => 'CLI-1',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'electrico',
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.proveedores.destroy', $proveedor));

    $response->assertRedirect();
    expect(Proveedor::find($proveedor->id))->not->toBeNull();
});

test('eliminar un proveedor con casos de pago asociados es rechazado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);
    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'proveedor_id' => $proveedor->id]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.proveedores.destroy', $proveedor));

    $response->assertRedirect();
    expect(Proveedor::find($proveedor->id))->not->toBeNull();
});

test('eliminar un proveedor con facturas asociadas es rechazado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);
    $caso = CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'proveedor_id' => $proveedor->id]);
    Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'proveedor_id' => $proveedor->id,
        'folio' => 'F-1',
        'monto' => 1000,
        'fecha_emision' => now(),
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.proveedores.destroy', $proveedor));

    $response->assertRedirect();
    expect(Proveedor::find($proveedor->id))->not->toBeNull();
});

test('eliminar un proveedor con procesos de adquisición asociados es rechazado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);
    $ccosto = crearCcostoParaDestroyProveedorTest();
    $modalidad = ModalidadAdquisicion::create(['codigo' => 'MOD-1', 'nombre' => 'Trato directo']);
    ProcesoAdquisicion::create([
        'codigo' => 'PA-1',
        'modalidad_id' => $modalidad->id,
        'ccosto_id' => $ccosto->id,
        'proveedor_id' => $proveedor->id,
        'objeto' => 'Compra de insumos',
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.proveedores.destroy', $proveedor));

    $response->assertRedirect();
    expect(Proveedor::find($proveedor->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar un proveedor', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.proveedores.destroy', $proveedor));

    $response->assertForbidden();
    expect(Proveedor::find($proveedor->id))->not->toBeNull();
});
