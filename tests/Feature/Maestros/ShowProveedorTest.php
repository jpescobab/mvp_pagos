<?php

use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con core_institucional.administrar puede ver el detalle de un proveedor', function () {
    $proveedor = Proveedor::create([
        'rutproveedor' => '76.234.567-8',
        'nombre' => 'Comercial Andes Sur Ltda.',
        'giro' => 'Insumos de oficina',
        'rubros' => ['insumos_oficina'],
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.proveedores.show', $proveedor));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/proveedores/show')
        ->where('proveedor.nombre', 'Comercial Andes Sur Ltda.')
        ->where('proveedor.giro', 'Insumos de oficina')
        ->where('tieneDocumentoRespaldo', false)
    );
});

test('un usuario sin core_institucional.administrar no puede ver el detalle de un proveedor', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76.234.567-8', 'nombre' => 'Comercial Andes Sur Ltda.']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.proveedores.show', $proveedor));

    $response->assertForbidden();
});
