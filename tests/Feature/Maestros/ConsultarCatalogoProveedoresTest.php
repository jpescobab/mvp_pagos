<?php

use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede listar el catálogo de proveedores sin filtro', function () {
    Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Proveedor Uno']);
    Proveedor::create(['rutproveedor' => '22222222-2', 'nombre' => 'Proveedor Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->get(route('maestros.proveedores.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/proveedores/index')
        ->has('proveedores.data', 2)
    );
});

test('buscar por nombre devuelve solo las coincidencias', function () {
    Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Constructora Andina']);
    Proveedor::create(['rutproveedor' => '22222222-2', 'nombre' => 'Servicios Patagonia']);

    $this->seed(RolesAndPermissionsSeeder::class);
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->get(route('maestros.proveedores.index', ['q' => 'Andina']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/proveedores/index')
        ->has('proveedores.data', 1)
        ->where('proveedores.data.0.nombre', 'Constructora Andina')
    );
});

test('buscar por rut devuelve solo las coincidencias', function () {
    Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Constructora Andina']);
    Proveedor::create(['rutproveedor' => '22222222-2', 'nombre' => 'Servicios Patagonia']);

    $this->seed(RolesAndPermissionsSeeder::class);
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->get(route('maestros.proveedores.index', ['q' => '22222222']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/proveedores/index')
        ->has('proveedores.data', 1)
        ->where('proveedores.data.0.nombre', 'Servicios Patagonia')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('maestros.proveedores.index'));

    $response->assertRedirect(route('login'));
});

test('un usuario sin core_institucional.administrar no puede listar proveedores', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('maestros.proveedores.index'));

    $response->assertForbidden();
});
