<?php

use App\Models\ExportacionInformeRazonado;
use App\Models\User;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Illuminate\Support\Facades\Storage;

// Los helpers corteReportabilidadDePrueba(), definicionInformeRazonadoDePrueba()
// y ejecucionEnElaboracionDePrueba() son globales dentro del directorio.

test('exportar en HTML con informes.exportar genera el archivo y registra la exportación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);
    app(InformeRazonadoService::class)->agregarMetrica($ejecucion, 'MET-1', 'Monto total', 100.0, 'CLP');
    $estadoInicial = $ejecucion->proceso->estadoActual->codigo;

    $response = $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'html']
    );

    $response->assertRedirect();
    expect($ejecucion->exportaciones()->count())->toBe(1);

    $exportacion = $ejecucion->exportaciones()->first();
    expect($exportacion->formato)->toBe('html');
    expect($exportacion->generado_por)->toBe($exportador->id);
    Storage::disk('local')->assertExists($exportacion->ruta_archivo);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe($estadoInicial);
});

test('exportar en un formato no soportado responde con error de validación y no registra nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);

    $response = $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'pdf']
    );

    $response->assertSessionHasErrors('formato');
    expect(ExportacionInformeRazonado::count())->toBe(0);
});

test('exportar sin informes.exportar responde 403 y no registra nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $sinPermiso = User::factory()->create();
    $ejecucion = ejecucionEnElaboracionDePrueba();

    $response = $this->actingAs($sinPermiso)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'html']
    );

    $response->assertForbidden();
    expect(ExportacionInformeRazonado::count())->toBe(0);
});

test('descargar una exportación con informes.exportar entrega el archivo', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);

    $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'html']
    )->assertRedirect();

    $exportacion = $ejecucion->exportaciones()->first();

    $response = $this->actingAs($exportador)->get(
        route('informes-razonados.exportaciones.descargar', $exportacion)
    );

    $response->assertOk();
    $response->assertDownload();
});
