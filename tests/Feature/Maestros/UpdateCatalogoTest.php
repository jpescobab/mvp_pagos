<?php

use App\Models\Catalogo;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede editar un catálogo', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $catalogo = Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-100', 'nombre' => 'Sueldo Base Funcionario']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.items.catalogos.update', [$item, $catalogo]), [
        'codigo' => 'CAT-100',
        'nombre' => 'Sueldo Base Funcionario Actualizado',
        'activo' => false,
    ]);

    $response->assertRedirect();

    $catalogo->refresh();
    expect($catalogo->nombre)->toBe('Sueldo Base Funcionario Actualizado');
    expect($catalogo->activo)->toBeFalse();
});

test('editar un catálogo con el código de otro falla la validación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-100', 'nombre' => 'Catálogo Uno']);
    $catalogo = Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-200', 'nombre' => 'Catálogo Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.items.catalogos.update', [$item, $catalogo]), [
        'codigo' => 'CAT-100',
        'nombre' => 'Catálogo Dos',
    ]);

    $response->assertInvalid(['codigo']);
    expect($catalogo->refresh()->codigo)->toBe('CAT-200');
});

test('un usuario sin core_institucional.administrar no puede editar un catálogo', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $catalogo = Catalogo::create(['item_id' => $item->id, 'codigo' => 'CAT-100', 'nombre' => 'Sueldo Base Funcionario']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('maestros.items.catalogos.update', [$item, $catalogo]), [
        'codigo' => 'CAT-100',
        'nombre' => 'Otro nombre',
    ]);

    $response->assertForbidden();
    expect($catalogo->refresh()->nombre)->toBe('Sueldo Base Funcionario');
});
