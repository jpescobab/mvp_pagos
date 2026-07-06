<?php

use App\Models\Asignacion;
use App\Models\Catalogo;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con core_institucional.administrar puede ver el detalle de un ítem', function () {
    $item = Item::create([
        'codigo' => 'ITEM-100',
        'nombre' => 'Remuneraciones',
        'descripcion' => 'Gastos de personal.',
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.items.show', $item));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/items/show')
        ->where('item.nombre', 'Remuneraciones')
        ->where('item.descripcion', 'Gastos de personal.')
    );
});

test('un usuario sin core_institucional.administrar no puede ver el detalle de un ítem', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.items.show', $item));

    $response->assertForbidden();
});

test('el detalle de un ítem incluye sus asignaciones y catálogos asociados', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-100', 'nombre' => 'Sueldos Base']);
    Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-100', 'nombre' => 'Sueldo Base Funcionario']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->get(route('maestros.items.show', $item));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('maestros/items/show')
        ->has('item.asignaciones', 1)
        ->where('item.asignaciones.0.codigo', 'ASIG-100')
        ->has('item.catalogos', 1)
        ->where('item.catalogos.0.codigo', 'CAT-100')
    );
});
