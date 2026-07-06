<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;

test('un usuario con indicadores.importar puede disparar la importación mensual manual', function () {
    Http::fake([
        '*/uf/*' => Http::response(['UFs' => []]),
        '*/utm/*' => Http::response(['UTMs' => []]),
        '*/ipc*' => Http::response(['IPCs' => []]),
    ]);

    $this->seed(RolesAndPermissionsSeeder::class);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('indicadores.importar');

    $response = $this->actingAs($usuario)->post(route('indicadores-economicos.importar-mensual'));

    $response->assertRedirect();
});

test('un usuario sin indicadores.importar no puede disparar la importación mensual manual', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('indicadores-economicos.importar-mensual'));

    $response->assertForbidden();
});
