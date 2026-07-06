<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con reportabilidad.ver puede listar los períodos de reportabilidad', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('reportabilidad.ver');

    $response = $this->actingAs($usuario)->get(route('reportabilidad.periodos.index'));

    $response->assertOk();
});

test('un usuario sin reportabilidad.ver no puede listar los períodos de reportabilidad', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('reportabilidad.periodos.index'));

    $response->assertForbidden();
});
