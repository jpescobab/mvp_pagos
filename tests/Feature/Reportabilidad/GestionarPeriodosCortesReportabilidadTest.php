<?php

use App\Models\CorteReportabilidad;
use App\Models\PeriodoReportabilidad;
use App\Models\User;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede abrir un período de reportabilidad', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('reportabilidad.periodos.store'), [
        'codigo' => '2026-06',
        'fecha_inicio' => '2026-06-01',
        'fecha_fin' => '2026-06-30',
    ]);

    $response->assertSessionHasNoErrors();

    $periodo = PeriodoReportabilidad::where('codigo', '2026-06')->first();
    expect($periodo)->not->toBeNull();
    expect($periodo->estado)->toBe('abierto');
});

test('un usuario autenticado puede crear un corte dentro de un período', function () {
    $periodo = PeriodoReportabilidad::create([
        'codigo' => '2026-07',
        'fecha_inicio' => '2026-07-01',
        'fecha_fin' => '2026-07-31',
        'estado' => 'abierto',
    ]);
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('reportabilidad.periodos.cortes.store', $periodo));

    $response->assertSessionHasNoErrors();

    $corte = CorteReportabilidad::where('periodo_reportabilidad_id', $periodo->id)->first();
    expect($corte)->not->toBeNull();
    expect($corte->estado)->toBe('borrador');
});

test('un usuario con el permiso reportabilidad.publicar_corte puede publicar un corte', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $periodo = PeriodoReportabilidad::create([
        'codigo' => '2026-08',
        'fecha_inicio' => '2026-08-01',
        'fecha_fin' => '2026-08-31',
        'estado' => 'abierto',
    ]);
    $corte = CorteReportabilidad::create([
        'periodo_reportabilidad_id' => $periodo->id,
        'fecha_corte' => now(),
        'estado' => 'borrador',
    ]);

    $admin = User::factory()->create();
    $admin->givePermissionTo('reportabilidad.publicar_corte');

    $response = $this->actingAs($admin)->post(route('reportabilidad.cortes.publicar', $corte));

    $response->assertSessionHasNoErrors();
    expect($corte->refresh()->estado)->toBe('publicado');
    expect($corte->publicado_por)->toBe($admin->id);
});

test('un usuario sin el permiso reportabilidad.publicar_corte no puede publicar un corte', function () {
    $periodo = PeriodoReportabilidad::create([
        'codigo' => '2026-09',
        'fecha_inicio' => '2026-09-01',
        'fecha_fin' => '2026-09-30',
        'estado' => 'abierto',
    ]);
    $corte = CorteReportabilidad::create([
        'periodo_reportabilidad_id' => $periodo->id,
        'fecha_corte' => now(),
        'estado' => 'borrador',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('reportabilidad.cortes.publicar', $corte));

    $response->assertSessionHasErrors('corte');
    expect($corte->refresh()->estado)->toBe('borrador');
});

test('el detalle de un corte incluye su período y los counts de items y snapshots', function () {
    $periodo = PeriodoReportabilidad::create([
        'codigo' => '2026-10',
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-10-31',
        'estado' => 'abierto',
    ]);
    $corte = CorteReportabilidad::create([
        'periodo_reportabilidad_id' => $periodo->id,
        'fecha_corte' => now(),
        'estado' => 'borrador',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('reportabilidad.cortes.show', $corte));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('reportabilidad/cortes/show')
        ->where('corte.periodo.codigo', '2026-10')
        ->where('corte.items_count', 0)
        ->where('corte.snapshots_count', 0)
    );
});
