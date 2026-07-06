<?php

use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede editar un ítem', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.items.update', $item), [
        'codigo' => 'ITEM-100',
        'nombre' => 'Remuneraciones y Honorarios',
        'descripcion' => 'Incluye honorarios a suma alzada.',
        'activo' => false,
    ]);

    $response->assertRedirect(route('maestros.items.show', $item));

    $item->refresh();
    expect($item->nombre)->toBe('Remuneraciones y Honorarios');
    expect($item->descripcion)->toBe('Incluye honorarios a suma alzada.');
    expect($item->activo)->toBeFalse();
});

test('editar un ítem con el código de otro ítem falla la validación', function () {
    Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Ítem Uno']);
    $item = Item::create(['codigo' => 'ITEM-200', 'nombre' => 'Ítem Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.items.update', $item), [
        'codigo' => 'ITEM-100',
        'nombre' => 'Ítem Dos',
    ]);

    $response->assertInvalid(['codigo']);
    expect($item->refresh()->codigo)->toBe('ITEM-200');
});

test('editar un ítem conservando su propio código no falla la validación', function () {
    $item = Item::create(['codigo' => 'ITEM-200', 'nombre' => 'Ítem Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.items.update', $item), [
        'codigo' => 'ITEM-200',
        'nombre' => 'Ítem Dos Actualizado',
    ]);

    $response->assertRedirect(route('maestros.items.show', $item));
    expect($item->refresh()->nombre)->toBe('Ítem Dos Actualizado');
});

test('un usuario sin core_institucional.administrar no puede editar un ítem', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $actor = User::factory()->create();

    $responseGet = $this->actingAs($actor)->get(route('maestros.items.edit', $item));
    $responseGet->assertForbidden();

    $responsePatch = $this->actingAs($actor)->patch(route('maestros.items.update', $item), [
        'codigo' => 'ITEM-100',
        'nombre' => 'Otro nombre',
    ]);
    $responsePatch->assertForbidden();
    expect($item->refresh()->nombre)->toBe('Remuneraciones');
});
