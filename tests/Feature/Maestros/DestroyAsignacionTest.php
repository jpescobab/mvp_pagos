<?php

use App\Models\Asignacion;
use App\Models\Item;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con core_institucional.administrar puede eliminar una asignación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $asignacion = Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-100', 'nombre' => 'Sueldos Base']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $response = $this->actingAs($actor)->delete(route('maestros.items.asignaciones.destroy', [$item, $asignacion]));

    $response->assertRedirect();
    expect(Asignacion::find($asignacion->id))->toBeNull();
    expect(Asignacion::withTrashed()->find($asignacion->id))->not->toBeNull();
});

test('un usuario sin core_institucional.administrar no puede eliminar una asignación', function () {
    $item = Item::create(['codigo' => 'ITEM-100', 'nombre' => 'Remuneraciones']);
    $asignacion = Asignacion::create(['item_id' => $item->id, 'codigo' => 'ASIG-100', 'nombre' => 'Sueldos Base']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('maestros.items.asignaciones.destroy', [$item, $asignacion]));

    $response->assertForbidden();
    expect(Asignacion::find($asignacion->id))->not->toBeNull();
});
