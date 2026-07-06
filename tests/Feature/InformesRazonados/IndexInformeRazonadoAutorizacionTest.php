<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('un usuario con informes.ver puede listar las definiciones de informes razonados', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('informes.ver');

    $response = $this->actingAs($usuario)->get(route('informes-razonados.definiciones.index'));

    $response->assertOk();
});

test('un usuario sin informes.ver no puede listar las definiciones de informes razonados', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('informes-razonados.definiciones.index'));

    $response->assertForbidden();
});

test('un usuario con informes.ver puede listar las ejecuciones de informes razonados', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('informes.ver');

    $response = $this->actingAs($usuario)->get(route('informes-razonados.ejecuciones.index'));

    $response->assertOk();
});

test('un usuario sin informes.ver no puede listar las ejecuciones de informes razonados', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('informes-razonados.ejecuciones.index'));

    $response->assertForbidden();
});
