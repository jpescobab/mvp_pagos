<?php

use App\Models\DefinicionInformeRazonado;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con informes.ver puede listar definiciones con búsqueda', function () {
    DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Presupuesto']);
    DefinicionInformeRazonado::create(['codigo' => 'INF-GASTO', 'nombre' => 'Gastos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.ver');

    $response = $this->actingAs($actor)->get(route('informes-razonados.definiciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('informes-razonados/definiciones/index')
        ->has('definiciones.data', 2)
        ->where('definiciones.data.0.ejecuciones_count', 0)
    );
});

test('buscar definiciones por nombre devuelve solo las coincidencias', function () {
    DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Presupuesto']);
    DefinicionInformeRazonado::create(['codigo' => 'INF-GASTO', 'nombre' => 'Gastos']);

    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.ver');

    $response = $this->actingAs($actor)->get(route('informes-razonados.definiciones.index', ['q' => 'Gastos']));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('informes-razonados/definiciones/index')
        ->has('definiciones.data', 1)
        ->where('definiciones.data.0.codigo', 'INF-GASTO')
    );
});

test('un usuario sin informes.ver no puede listar definiciones', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('informes-razonados.definiciones.index'));

    $response->assertForbidden();
});
