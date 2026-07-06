<?php

use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede registrar un ítem con datos mínimos', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.items.store'), [
        'codigo' => 'ITEM-100',
        'nombre' => 'Remuneraciones',
    ]);

    $response->assertRedirect(route('maestros.items.index'));

    $item = Item::where('codigo', 'ITEM-100')->first();
    expect($item)->not->toBeNull();
    expect($item->nombre)->toBe('Remuneraciones');
    expect($item->activo)->toBeTrue();
    expect($item->descripcion)->toBeNull();
});

test('un usuario con core_institucional.administrar puede registrar un ítem con todos los datos', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.items.store'), [
        'codigo' => 'ITEM-200',
        'nombre' => 'Bienes y Servicios de Consumo',
        'descripcion' => 'Clasificador de gastos operacionales.',
        'activo' => false,
    ]);

    $response->assertRedirect(route('maestros.items.index'));

    $item = Item::where('codigo', 'ITEM-200')->first();
    expect($item)->not->toBeNull();
    expect($item->descripcion)->toBe('Clasificador de gastos operacionales.');
    expect($item->activo)->toBeFalse();
});

test('registrar un ítem con un código ya existente falla la validación', function () {
    Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Ítem Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.items.store'), [
        'codigo' => 'ITEM-100',
        'nombre' => 'Otro Ítem',
    ]);

    $response->assertInvalid(['codigo']);
    expect(Item::where('codigo', 'ITEM-100')->count())->toBe(1);
});

test('un usuario sin core_institucional.administrar no puede registrar un ítem', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.items.store'), [
        'codigo' => 'ITEM-100',
        'nombre' => 'Ítem Nuevo',
    ]);

    $response->assertForbidden();
    expect(Item::where('codigo', 'ITEM-100')->count())->toBe(0);
});

test('un usuario sin core_institucional.administrar no puede acceder al formulario de alta', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('maestros.items.create'));

    $response->assertForbidden();
});
