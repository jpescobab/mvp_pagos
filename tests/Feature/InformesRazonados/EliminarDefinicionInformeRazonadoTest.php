<?php

use App\Models\CorteReportabilidad;
use App\Models\DefinicionInformeRazonado;
use App\Models\EjecucionInformeRazonado;
use App\Models\PeriodoReportabilidad;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

function cortePublicadoParaEliminarDefinicionTest(): CorteReportabilidad
{
    $periodo = PeriodoReportabilidad::create([
        'codigo' => 'PERIODO-'.uniqid(),
        'fecha_inicio' => '2026-06-01',
        'fecha_fin' => '2026-06-30',
        'estado' => 'abierto',
    ]);

    return CorteReportabilidad::create([
        'periodo_reportabilidad_id' => $periodo->id,
        'fecha_corte' => now(),
        'estado' => 'publicado',
    ]);
}

test('un usuario con informes.administrar puede eliminar una definición sin ejecuciones', function () {
    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-SOLA', 'nombre' => 'Sin ejecuciones']);

    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.administrar');

    $response = $this->actingAs($actor)->delete(route('informes-razonados.definiciones.destroy', $definicion));

    $response->assertRedirect(route('informes-razonados.definiciones.index'));
    expect(DefinicionInformeRazonado::find($definicion->id))->toBeNull();
});

test('eliminar una definición con ejecuciones asociadas es rechazado', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Con ejecuciones']);
    $corte = cortePublicadoParaEliminarDefinicionTest();
    $ejecucion = app(InformeRazonadoService::class)->iniciarEjecucion($definicion, $corte);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.administrar');

    $response = $this->actingAs($actor)->delete(route('informes-razonados.definiciones.destroy', $definicion));

    $response->assertRedirect();
    expect(DefinicionInformeRazonado::find($definicion->id))->not->toBeNull();
    expect(EjecucionInformeRazonado::find($ejecucion->id))->not->toBeNull();
});

test('un usuario sin informes.administrar no puede eliminar una definición', function () {
    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Presupuesto']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->delete(route('informes-razonados.definiciones.destroy', $definicion));

    $response->assertForbidden();
    expect(DefinicionInformeRazonado::find($definicion->id))->not->toBeNull();
});
