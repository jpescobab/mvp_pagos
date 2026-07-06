<?php

use App\Models\Catalogo;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede registrar un catálogo bajo un ítem', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.items.catalogos.store', $item), [
        'codigo' => 'CAT-100',
        'nombre' => 'Sueldo Base Funcionario',
    ]);

    $response->assertRedirect();

    $catalogo = Catalogo::where('codigo', 'CAT-100')->first();
    expect($catalogo)->not->toBeNull();
    expect($catalogo->item_id)->toBe($item->id);
    expect($catalogo->activo)->toBeTrue();
});

test('registrar un catálogo con un código ya existente falla la validación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-100', 'nombre' => 'Catálogo Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.items.catalogos.store', $item), [
        'codigo' => 'CAT-100',
        'nombre' => 'Otro Catálogo',
    ]);

    $response->assertInvalid(['codigo']);
    expect(Catalogo::where('codigo', 'CAT-100')->count())->toBe(1);
});

test('un usuario sin core_institucional.administrar no puede registrar un catálogo', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.items.catalogos.store', $item), [
        'codigo' => 'CAT-100',
        'nombre' => 'Sueldo Base Funcionario',
    ]);

    $response->assertForbidden();
    expect(Catalogo::where('codigo', 'CAT-100')->count())->toBe(0);
});
