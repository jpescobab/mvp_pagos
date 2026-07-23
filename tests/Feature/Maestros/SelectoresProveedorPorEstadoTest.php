<?php

use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * El estado del proveedor solo gobierna los selectores donde se elige un
 * proveedor PARA OPERAR. El catálogo de Maestros muestra los tres estados:
 * si filtrara, un borrador sería invisible justo en la pantalla desde la que
 * hay que completarlo.
 */
beforeEach(function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->borrador = Proveedor::create([
        'rutproveedor' => '80.111.111-1',
        'nombre' => 'Proveedor Borrador SpA',
        'estado' => Proveedor::ESTADO_BORRADOR,
    ]);
    $this->activo = Proveedor::create([
        'rutproveedor' => '80.222.222-2',
        'nombre' => 'Proveedor Activo SpA',
        'estado' => Proveedor::ESTADO_ACTIVO,
    ]);
    $this->inactivo = Proveedor::create([
        'rutproveedor' => '80.333.333-3',
        'nombre' => 'Proveedor Inactivo SpA',
        'estado' => Proveedor::ESTADO_INACTIVO,
    ]);

    $this->actor = User::factory()->create();
    $this->actor->givePermissionTo('core_institucional.administrar');
});

test('el formulario de creación de proceso de adquisición ofrece solo proveedores activos', function () {
    $response = $this->actingAs($this->actor)->get(route('adquisiciones.procesos.create'));

    $response->assertOk();

    $ids = collect($response->inertiaProps('proveedores'))->pluck('id')->all();

    expect($ids)->toBe([$this->activo->id]);
});

test('el formulario de creación de cliente medidor ofrece solo proveedores activos', function () {
    $response = $this->actingAs($this->actor)->get(route('maestros.clientes-medidores.create'));

    $response->assertOk();

    $ids = collect($response->inertiaProps('proveedores'))->pluck('id')->all();

    expect($ids)->toBe([$this->activo->id]);
});

test('el catálogo de proveedores sigue mostrando los tres estados', function () {
    $response = $this->actingAs($this->actor)->get(route('maestros.proveedores.index'));

    $response->assertOk();

    $estados = collect($response->inertiaProps('proveedores.data'))->pluck('estado')->sort()->values()->all();

    expect($estados)->toBe(['activo', 'borrador', 'inactivo']);
});
