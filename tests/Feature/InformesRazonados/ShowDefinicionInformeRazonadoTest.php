<?php

use App\Models\CorteReportabilidad;
use App\Models\DefinicionInformeRazonado;
use App\Models\PeriodoReportabilidad;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function cortePublicadoParaShowDefinicionTest(): CorteReportabilidad
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

test('un usuario con informes.ver ve el detalle de una definición con sus ejecuciones', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Presupuesto']);
    $corte = cortePublicadoParaShowDefinicionTest();

    $generador = User::factory()->create();
    app(InformeRazonadoService::class)->iniciarEjecucion($definicion, $corte, $generador);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.ver');

    $response = $this->actingAs($actor)->get(route('informes-razonados.definiciones.show', $definicion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('informes-razonados/definiciones/show')
        ->where('definicion.codigo', 'INF-PRES')
        ->has('definicion.ejecuciones', 1)
        ->where('definicion.ejecuciones.0.estado', 'En elaboración')
    );
});

test('el detalle de una definición sin ejecuciones se muestra con la lista vacía', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-SOLA', 'nombre' => 'Sin ejecuciones']);

    $actor = User::factory()->create();
    $actor->givePermissionTo('informes.ver');

    $response = $this->actingAs($actor)->get(route('informes-razonados.definiciones.show', $definicion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('informes-razonados/definiciones/show')
        ->where('definicion.nombre', 'Sin ejecuciones')
        ->has('definicion.ejecuciones', 0)
    );
});

test('un usuario sin informes.ver no puede ver el detalle de una definición', function () {
    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-PRES', 'nombre' => 'Presupuesto']);

    $actor = User::factory()->create();

    $response = $this->actingAs($actor)->get(route('informes-razonados.definiciones.show', $definicion));

    $response->assertForbidden();
});
