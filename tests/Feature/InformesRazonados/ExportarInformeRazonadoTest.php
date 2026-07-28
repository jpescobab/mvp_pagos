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

test('exportar en PDF con informes.exportar genera el archivo y registra la exportación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);
    app(InformeRazonadoService::class)->agregarMetrica($ejecucion, 'MET-1', 'Monto total', 100.0, 'CLP');
    $estadoInicial = $ejecucion->proceso->estadoActual->codigo;

    $response = $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'pdf']
    );

    $response->assertRedirect();
    expect($ejecucion->exportaciones()->count())->toBe(1);

    $exportacion = $ejecucion->exportaciones()->first();
    expect($exportacion->formato)->toBe('pdf');
    expect($exportacion->generado_por)->toBe($exportador->id);
    Storage::disk('local')->assertExists($exportacion->ruta_archivo);
    expect(Storage::disk('local')->get($exportacion->ruta_archivo))->toStartWith('%PDF-');
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe($estadoInicial);
});

test('descargar una exportación PDF responde con Content-Type application/pdf', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);

    $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'pdf']
    )->assertRedirect();

    $exportacion = $ejecucion->exportaciones()->first();

    $response = $this->actingAs($exportador)->get(
        route('informes-razonados.exportaciones.descargar', $exportacion)
    );

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('exportar en Word con informes.exportar genera el archivo y registra la exportación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);
    app(InformeRazonadoService::class)->agregarMetrica($ejecucion, 'MET-1', 'Monto total', 100.0, 'CLP');
    $estadoInicial = $ejecucion->proceso->estadoActual->codigo;

    $response = $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'docx']
    );

    $response->assertRedirect();
    expect($ejecucion->exportaciones()->count())->toBe(1);

    $exportacion = $ejecucion->exportaciones()->first();
    expect($exportacion->formato)->toBe('docx');
    expect($exportacion->generado_por)->toBe($exportador->id);
    Storage::disk('local')->assertExists($exportacion->ruta_archivo);
    expect(Storage::disk('local')->get($exportacion->ruta_archivo))->toStartWith('PK');
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe($estadoInicial);
});

test('exportar en Excel con informes.exportar genera el archivo y registra la exportación', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);
    app(InformeRazonadoService::class)->agregarMetrica($ejecucion, 'MET-1', 'Monto total', 100.0, 'CLP');
    $estadoInicial = $ejecucion->proceso->estadoActual->codigo;

    $response = $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'xlsx']
    );

    $response->assertRedirect();
    expect($ejecucion->exportaciones()->count())->toBe(1);

    $exportacion = $ejecucion->exportaciones()->first();
    expect($exportacion->formato)->toBe('xlsx');
    expect($exportacion->generado_por)->toBe($exportador->id);
    Storage::disk('local')->assertExists($exportacion->ruta_archivo);
    expect(Storage::disk('local')->get($exportacion->ruta_archivo))->toStartWith('PK');
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe($estadoInicial);
});

test('descargar exportaciones Word y Excel responde con su Content-Type OOXML', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);

    $tipos = [
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    foreach ($tipos as $formato => $contentType) {
        $this->actingAs($exportador)->post(
            route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
            ['formato' => $formato]
        )->assertRedirect();

        $exportacion = $ejecucion->exportaciones()->where('formato', $formato)->latest('id')->first();

        $response = $this->actingAs($exportador)->get(
            route('informes-razonados.exportaciones.descargar', $exportacion)
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', $contentType);
    }
});

test('exportar en un formato no soportado responde con error de validación y no registra nada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);

    $response = $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'txt']
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

test('la exportación HTML incluye el gráfico como SVG y no como JSON crudo', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);
    app(InformeRazonadoService::class)->agregarGrafico($ejecucion, 'GRA-1', 'Evolución mensual', 'barra', [
        'categorias' => ['Ene', 'Feb', 'Mar'],
        'series' => [['nombre' => 'Total', 'valores' => [10, 20, 30]]],
    ]);

    $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'html']
    )->assertRedirect();

    $exportacion = $ejecucion->exportaciones()->first();
    $contenido = Storage::disk('local')->get($exportacion->ruta_archivo);

    expect($contenido)->toContain('<svg')
        ->and($contenido)->toContain('Evolución mensual')
        ->and($contenido)->not->toContain('<pre>');
});

test('un gráfico con datos vacíos no rompe la exportación y muestra el fallback', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);
    app(InformeRazonadoService::class)->agregarGrafico($ejecucion, 'GRA-1', 'Sin datos', 'linea', []);

    $this->actingAs($exportador)->post(
        route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
        ['formato' => 'html']
    )->assertRedirect();

    $exportacion = $ejecucion->exportaciones()->first();
    $contenido = Storage::disk('local')->get($exportacion->ruta_archivo);

    expect($contenido)->toContain('Sin datos para graficar');
});

test('los cuatro formatos se generan sin excepción con un gráfico presente', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);
    Storage::fake('local');

    $exportador = User::factory()->create();
    $exportador->givePermissionTo('informes.exportar');

    $ejecucion = ejecucionEnElaboracionDePrueba($exportador);
    app(InformeRazonadoService::class)->agregarGrafico($ejecucion, 'GRA-1', 'Distribución', 'torta', [
        'categorias' => ['A', 'B', 'C'],
        'series' => [['nombre' => 'Participación', 'valores' => [3, 5, 2]]],
    ]);

    foreach (['html', 'pdf', 'docx', 'xlsx'] as $formato) {
        $this->actingAs($exportador)->post(
            route('informes-razonados.ejecuciones.exportaciones.store', $ejecucion),
            ['formato' => $formato]
        )->assertRedirect();

        $exportacion = $ejecucion->exportaciones()->where('formato', $formato)->latest('id')->first();
        Storage::disk('local')->assertExists($exportacion->ruta_archivo);
    }
});
