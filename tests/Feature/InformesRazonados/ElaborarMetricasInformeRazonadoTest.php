<?php

use App\Models\MetricaInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;

// Los helpers corteReportabilidadDePrueba(), definicionInformeRazonadoDePrueba()
// y ejecucionEnElaboracionDePrueba() son globales dentro del directorio.

test('agregar una métrica con informes.elaborar en una ejecución en elaboración la crea', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $estadoInicial = $ejecucion->proceso->estadoActual->codigo;

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.metricas.store', $ejecucion),
        ['codigo' => 'MET-1', 'etiqueta' => 'Monto total', 'valor' => 1000.5, 'unidad' => 'CLP', 'orden' => 1]
    );

    $response->assertRedirect();
    expect($ejecucion->metricas()->count())->toBe(1);
    expect($ejecucion->metricas()->first()->etiqueta)->toBe('Monto total');
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe($estadoInicial);
});

test('agregar una métrica sin informes.elaborar responde 403 y no crea nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $sinPermiso = User::factory()->create();
    $ejecucion = ejecucionEnElaboracionDePrueba();

    $response = $this->actingAs($sinPermiso)->post(
        route('informes-razonados.ejecuciones.metricas.store', $ejecucion),
        ['codigo' => 'X', 'etiqueta' => 'No autorizada']
    );

    $response->assertForbidden();
    expect(MetricaInformeRazonado::count())->toBe(0);
});

test('editar una métrica con informes.elaborar actualiza sus campos', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $metrica = app(InformeRazonadoService::class)->agregarMetrica($ejecucion, 'MET-1', 'Viejo', 1.0, 'UF');

    $response = $this->actingAs($elaborador)->patch(
        route('informes-razonados.metricas.update', $metrica),
        ['etiqueta' => 'Nuevo', 'valor' => 99.9, 'unidad' => 'CLP', 'orden' => 4]
    );

    $response->assertRedirect();
    $metrica->refresh();
    expect($metrica->etiqueta)->toBe('Nuevo');
    expect((float) $metrica->valor)->toBe(99.9);
    expect($metrica->orden)->toBe(4);
});

test('eliminar una métrica con informes.elaborar la borra', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $metrica = app(InformeRazonadoService::class)->agregarMetrica($ejecucion, 'MET-1', 'Descartable');

    $response = $this->actingAs($elaborador)->delete(
        route('informes-razonados.metricas.destroy', $metrica)
    );

    $response->assertRedirect();
    expect(MetricaInformeRazonado::whereKey($metrica->id)->exists())->toBeFalse();
});

test('agregar una métrica con una sección de otra ejecución es rechazado por validación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucionA = ejecucionEnElaboracionDePrueba($elaborador);
    $ejecucionB = ejecucionEnElaboracionDePrueba($elaborador);
    $seccionDeB = $servicio->agregarSeccion($ejecucionB, 'C1', 'De otra', 0);

    $response = $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.metricas.store', $ejecucionA),
        ['codigo' => 'MET-1', 'etiqueta' => 'Cruzada', 'seccion_informe_razonado_id' => $seccionDeB->id]
    );

    $response->assertSessionHasErrors('seccion_informe_razonado_id');
    expect($ejecucionA->metricas()->count())->toBe(0);
});

test('no se pueden elaborar métricas cuando la ejecución no está en elaboración', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $elaborador = User::factory()->create();
    $elaborador->givePermissionTo('informes.elaborar');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = ejecucionEnElaboracionDePrueba($elaborador);
    $metrica = $servicio->agregarMetrica($ejecucion, 'MET-1', 'En elaboración');

    $servicio->enviarARevision($ejecucion, $elaborador);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');

    $this->actingAs($elaborador)->post(
        route('informes-razonados.ejecuciones.metricas.store', $ejecucion),
        ['codigo' => 'MET-2', 'etiqueta' => 'Fuera']
    )->assertForbidden();

    $this->actingAs($elaborador)->patch(
        route('informes-razonados.metricas.update', $metrica),
        ['etiqueta' => 'Editada fuera']
    )->assertForbidden();

    $this->actingAs($elaborador)->delete(
        route('informes-razonados.metricas.destroy', $metrica)
    )->assertForbidden();

    expect($ejecucion->metricas()->count())->toBe(1);
    expect($metrica->refresh()->etiqueta)->toBe('En elaboración');
});
