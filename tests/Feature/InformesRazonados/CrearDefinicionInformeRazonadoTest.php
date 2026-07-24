<?php

use App\Models\DefinicionInformeRazonado;
use App\Models\User;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

test('un usuario con informes.administrar puede crear una definición', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.administrar');

    $response = $this->actingAs($actor)->post(route('informes-razonados.definiciones.store'), [
        'codigo' => 'INFORME-PRESUPUESTO',
        'nombre' => 'Informe de presupuesto',
    ]);

    $response->assertRedirect(route('informes-razonados.definiciones.index'));

    $definicion = DefinicionInformeRazonado::where('codigo', 'INFORME-PRESUPUESTO')->first();
    expect($definicion)->not->toBeNull();
    expect($definicion->activo)->toBeTrue();
});

test('un usuario sin informes.administrar no puede crear una definición', function () {
    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->post(route('informes-razonados.definiciones.store'), [
        'codigo' => 'INFORME-PRESUPUESTO',
        'nombre' => 'Informe de presupuesto',
    ]);

    $response->assertForbidden();
    expect(DefinicionInformeRazonado::count())->toBe(0);
});

test('crear una definición con un código ya existente falla la validación', function () {
    DefinicionInformeRazonado::create(['codigo' => 'INFORME-PRESUPUESTO', 'nombre' => 'Existente']);

    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.administrar');

    $response = $this->actingAs($actor)->post(route('informes-razonados.definiciones.store'), [
        'codigo' => 'INFORME-PRESUPUESTO',
        'nombre' => 'Duplicada',
    ]);

    $response->assertSessionHasErrors('codigo');
    expect(DefinicionInformeRazonado::count())->toBe(1);
});
