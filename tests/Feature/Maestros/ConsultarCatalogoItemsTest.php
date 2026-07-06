<?php

use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede listar el catálogo de ítems presupuestarios sin filtro', function () {
    Item::create(['codigo' => 'ITEM-1', 'nombre' => 'Ítem Uno']);
    Item::create(['codigo' => 'ITEM-2', 'nombre' => 'Ítem Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->get(route('maestros.items.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/items/index')
        ->has('items.data', 2)
    );
});

test('buscar por nombre devuelve solo las coincidencias', function () {
    Item::create(['codigo' => 'ITEM-1', 'nombre' => 'Remuneraciones']);
    Item::create(['codigo' => 'ITEM-2', 'nombre' => 'Bienes y Servicios']);

    $this->seed(RolesAndPermissionsSeeder::class);
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->get(route('maestros.items.index', ['q' => 'Remuner']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/items/index')
        ->has('items.data', 1)
        ->where('items.data.0.nombre', 'Remuneraciones')
    );
});

test('buscar por código devuelve solo las coincidencias', function () {
    Item::create(['codigo' => 'ITEM-1', 'nombre' => 'Remuneraciones']);
    Item::create(['codigo' => 'ITEM-2', 'nombre' => 'Bienes y Servicios']);

    $this->seed(RolesAndPermissionsSeeder::class);
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($usuario)->get(route('maestros.items.index', ['q' => 'ITEM-2']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/items/index')
        ->has('items.data', 1)
        ->where('items.data.0.nombre', 'Bienes y Servicios')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('maestros.items.index'));

    $response->assertRedirect(route('login'));
});

test('un usuario sin core_institucional.administrar no puede listar ítems presupuestarios', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('maestros.items.index'));

    $response->assertForbidden();
});
