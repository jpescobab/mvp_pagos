<?php

use App\Models\Asignacion;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede editar una asignación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $asignacion = Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-100', 'nombre' => 'Sueldos Base']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.items.asignaciones.update', [$item, $asignacion]), [
        'codigo' => 'ASIG-100',
        'nombre' => 'Sueldos Base Actualizado',
        'activo' => false,
    ]);

    $response->assertRedirect();

    $asignacion->refresh();
    expect($asignacion->nombre)->toBe('Sueldos Base Actualizado');
    expect($asignacion->activo)->toBeFalse();
});

test('editar una asignación con el código de otra falla la validación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-100', 'nombre' => 'Asignación Uno']);
    $asignacion = Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-200', 'nombre' => 'Asignación Dos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->patch(route('maestros.items.asignaciones.update', [$item, $asignacion]), [
        'codigo' => 'ASIG-100',
        'nombre' => 'Asignación Dos',
    ]);

    $response->assertInvalid(['codigo']);
    expect($asignacion->refresh()->codigo)->toBe('ASIG-200');
});

test('un usuario sin core_institucional.administrar no puede editar una asignación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $asignacion = Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-100', 'nombre' => 'Sueldos Base']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('maestros.items.asignaciones.update', [$item, $asignacion]), [
        'codigo' => 'ASIG-100',
        'nombre' => 'Otro nombre',
    ]);

    $response->assertForbidden();
    expect($asignacion->refresh()->nombre)->toBe('Sueldos Base');
});
