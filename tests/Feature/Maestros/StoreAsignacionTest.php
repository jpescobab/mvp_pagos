<?php

use App\Models\Asignacion;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede registrar una asignación bajo un ítem', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.items.asignaciones.store', $item), [
        'codigo' => 'ASIG-100',
        'nombre' => 'Sueldos Base',
    ]);

    $response->assertRedirect();

    $asignacion = Asignacion::where('codigo', 'ASIG-100')->first();
    expect($asignacion)->not->toBeNull();
    expect($asignacion->item_id)->toBe($item->id);
    expect($asignacion->activo)->toBeTrue();
});

test('registrar una asignación con un código ya existente falla la validación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-100', 'nombre' => 'Asignación Existente']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->post(route('maestros.items.asignaciones.store', $item), [
        'codigo' => 'ASIG-100',
        'nombre' => 'Otra Asignación',
    ]);

    $response->assertInvalid(['codigo']);
    expect(Asignacion::where('codigo', 'ASIG-100')->count())->toBe(1);
});

test('un usuario sin core_institucional.administrar no puede registrar una asignación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('maestros.items.asignaciones.store', $item), [
        'codigo' => 'ASIG-100',
        'nombre' => 'Sueldos Base',
    ]);

    $response->assertForbidden();
    expect(Asignacion::where('codigo', 'ASIG-100')->count())->toBe(0);
});
