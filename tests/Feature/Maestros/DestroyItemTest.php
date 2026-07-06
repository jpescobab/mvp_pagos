<?php

use App\Models\Asignacion;
use App\Models\Catalogo;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede eliminar un ítem sin relaciones', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.items.destroy', $item));

    $response->assertRedirect(route('maestros.items.index'));
    expect(Item::find($item->id))->toBeNull();
    expect(Item::withTrashed()->find($item->id))->not->toBeNull();
});

test('eliminar un ítem con asignaciones asociadas es rechazado', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-1', 'nombre' => 'Asignación 1']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.items.destroy', $item));

    $response->assertRedirect();
    expect(Item::find($item->id))->not->toBeNull();
});

test('eliminar un ítem con catálogos asociados es rechazado', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-1', 'nombre' => 'Catálogo 1']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.items.destroy', $item));

    $response->assertRedirect();
    expect(Item::find($item->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar un ítem', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.items.destroy', $item));

    $response->assertForbidden();
    expect(Item::find($item->id))->not->toBeNull();
});
