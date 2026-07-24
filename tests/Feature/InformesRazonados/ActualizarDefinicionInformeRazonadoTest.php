<?php

use App\Models\DefinicionInformeRazonado;
use App\Models\User;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

test('un usuario con informes.administrar puede editar una definición', function () {
    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Nombre Viejo']);

    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.administrar');

    $response = $this->actingAs($actor)->patch(route('informes-razonados.definiciones.update', $definicion), [
        'codigo' => 'INF-PRES',
        'nombre' => 'Nombre Nuevo',
        'activo' => false,
    ]);

    $response->assertRedirect(route('informes-razonados.definiciones.show', $definicion));

    $definicion->refresh();
    expect($definicion->nombre)->toBe('Nombre Nuevo');
    expect($definicion->activo)->toBeFalse();
});

test('guardar una definición sin cambiar su código no la reporta como duplicada consigo misma', function () {
    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Nombre Viejo']);

    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.administrar');

    $response = $this->actingAs($actor)->patch(route('informes-razonados.definiciones.update', $definicion), [
        'codigo' => 'INF-PRES',
        'nombre' => 'Nombre Corregido',
    ]);

    $response->assertSessionHasNoErrors();
    expect($definicion->refresh()->nombre)->toBe('Nombre Corregido');
});

test('editar una definición con el código de otra falla la validación', function () {
    DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Primera']);
    $otra = DefinicionInformeRazonado::create(['codigo' => 'INF-GASTO', 'nombre' => 'Segunda']);

    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.administrar');

    $response = $this->actingAs($actor)->patch(route('informes-razonados.definiciones.update', $otra), [
        'codigo' => 'INF-PRES',
        'nombre' => 'Segunda',
    ]);

    $response->assertSessionHasErrors('codigo');
    expect($otra->refresh()->codigo)->toBe('INF-GASTO');
});

test('un usuario sin informes.administrar no puede editar una definición', function () {
    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Nombre Viejo']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->patch(route('informes-razonados.definiciones.update', $definicion), [
        'codigo' => 'INF-PRES',
        'nombre' => 'Nombre Nuevo',
    ]);

    $response->assertForbidden();
    expect($definicion->refresh()->nombre)->toBe('Nombre Viejo');
});
