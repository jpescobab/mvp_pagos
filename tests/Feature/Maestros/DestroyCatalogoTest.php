<?php

use App\Models\Catalogo;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede eliminar un catálogo', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $catalogo = Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-100', 'nombre' => 'Sueldo Base Funcionario']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.items.catalogos.destroy', [$item, $catalogo]));

    $response->assertRedirect();
    expect(Catalogo::find($catalogo->id))->toBeNull();
    expect(Catalogo::withTrashed()->find($catalogo->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar un catálogo', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $catalogo = Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-100', 'nombre' => 'Sueldo Base Funcionario']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.items.catalogos.destroy', [$item, $catalogo]));

    $response->assertForbidden();
    expect(Catalogo::find($catalogo->id))->not->toBeNull();
});
