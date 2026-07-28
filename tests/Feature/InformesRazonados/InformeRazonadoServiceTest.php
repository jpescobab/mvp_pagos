<?php

use App\Exceptions\CorteReportabilidadException;
use App\Exceptions\TransicionWorkflowException;
use App\Models\CorteReportabilidad;
use App\Models\DefinicionInformeRazonado;
use App\Models\EjecucionInformeRazonado;
use App\Models\PeriodoReportabilidad;
use App\Models\User;
use App\Services\InformesRazonados\ExportadorInformeRazonadoService;
use App\Services\InformesRazonados\InformeRazonadoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * @param  array<string, mixed>  $overrides
 */
function corteReportabilidadDePrueba(array $overrides = []): CorteReportabilidad
{
    $periodo = PeriodoReportabilidad::create([
        'codigo' => 'PERIODO-'.uniqid(),
        'fecha_inicio' => '2026-06-01',
        'fecha_fin' => '2026-06-30',
        'estado' => 'abierto',
    ]);

    return CorteReportabilidad::create(array_merge([
        'periodo_reportabilidad_id' => $periodo->id,
        'fecha_corte' => now(),
        'estado' => 'borrador',
    ], $overrides));
}

function definicionInformeRazonadoDePrueba(): DefinicionInformeRazonado
{
    return DefinicionInformeRazonado::create([
        'codigo' => 'INFORME-'.uniqid(),
        'nombre' => 'Informe de prueba',
        'activo' => true,
    ]);
}

test('iniciarEjecucion rechaza un corte no publicado y no crea ninguna ejecución', function () {
    $corte = corteReportabilidadDePrueba(['estado' => 'borrador']);
    $definicion = definicionInformeRazonadoDePrueba();

    expect(fn () => app(InformeRazonadoService::class)->iniciarEjecucion($definicion, $corte))
        ->toThrow(CorteReportabilidadException::class);

    expect(EjecucionInformeRazonado::count())->toBe(0);
});

test('iniciarEjecucion sobre un corte publicado crea la ejecución con su Proceso en el estado inicial', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();

    $ejecucion = app(InformeRazonadoService::class)->iniciarEjecucion($definicion, $corte);

    expect($ejecucion->corte_reportabilidad_id)->toBe($corte->id);
    expect($ejecucion->proceso)->not->toBeNull();
    expect($ejecucion->proceso->estadoActual->codigo)->toBe('en_elaboracion');
});

test('el ciclo enviarARevision -> aprobar -> publicar transiciona el Proceso y crea evidencia', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $servicio = app(InformeRazonadoService::class);

    $ejecucion = $servicio->iniciarEjecucion($definicion, $corte, $admin);

    $servicio->enviarARevision($ejecucion, $admin);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('en_revision');

    $servicio->aprobar($ejecucion, 'Todo conforme', $admin);
    expect($ejecucion->proceso->refresh()->estadoActual->codigo)->toBe('aprobado');
    expect($ejecucion->aprobaciones)->toHaveCount(1);
    expect($ejecucion->aprobaciones->first()->decision)->toBe('aprobado');

    $servicio->publicar($ejecucion, $admin);
    $procesoFinal = $ejecucion->proceso->refresh();
    expect($procesoFinal->estadoActual->codigo)->toBe('publicado');
    expect($procesoFinal->cerrado_en)->not->toBeNull();
    expect($ejecucion->snapshots)->toHaveCount(1);
});

test('aprobar exige el permiso informes.aprobar y rechazar exige comentario', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $usuarioSinPermiso = User::factory()->create();

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $servicio = app(InformeRazonadoService::class);

    $ejecucion = $servicio->iniciarEjecucion($definicion, $corte, $admin);
    $servicio->enviarARevision($ejecucion, $admin);

    expect(fn () => $servicio->aprobar($ejecucion, usuario: $usuarioSinPermiso))
        ->toThrow(TransicionWorkflowException::class);

    expect(fn () => $servicio->rechazar($ejecucion, '', $admin))
        ->toThrow(TransicionWorkflowException::class);
});

test('una narrativa generada por IA queda sin revisar hasta que se revisa explícitamente', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $servicio = app(InformeRazonadoService::class);
    $usuario = User::factory()->create();

    $ejecucion = $servicio->iniciarEjecucion($definicion, $corte, $usuario);
    $narrativa = $servicio->agregarNarrativa($ejecucion, 'Texto generado automáticamente', generadoPorIa: true);

    expect($narrativa->generado_por_ia)->toBeTrue();
    expect($narrativa->revisado_por)->toBeNull();
    expect($narrativa->revisado_en)->toBeNull();

    $revisada = $servicio->revisarNarrativa($narrativa, $usuario);

    expect($revisada->revisado_por)->toBe($usuario->id);
    expect($revisada->revisado_en)->not->toBeNull();
});

test('exportar crea una ExportacionInformeRazonado con formato, ruta y responsable', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteReportabilidadDePrueba(['estado' => 'publicado']);
    $definicion = definicionInformeRazonadoDePrueba();
    $servicio = app(InformeRazonadoService::class);
    $usuario = User::factory()->create();

    $ejecucion = $servicio->iniciarEjecucion($definicion, $corte, $usuario);
    $exportacion = $servicio->exportar($ejecucion, 'pdf', 'storage/informes/informe.pdf', $usuario);

    expect($exportacion->formato)->toBe('pdf');
    expect($exportacion->ruta_archivo)->toBe('storage/informes/informe.pdf');
    expect($exportacion->generado_por)->toBe($usuario->id);
});

test('editarMetrica actualiza los campos y eliminarMetrica la borra', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = $servicio->iniciarEjecucion(
        definicionInformeRazonadoDePrueba(),
        corteReportabilidadDePrueba(['estado' => 'publicado']),
    );

    $metrica = $servicio->agregarMetrica($ejecucion, 'MET-1', 'Monto total', 100.0, 'CLP');

    $editada = $servicio->editarMetrica($metrica, 'Monto neto', 250.5, 'UF', 3);
    expect($editada->etiqueta)->toBe('Monto neto');
    expect((float) $editada->valor)->toBe(250.5);
    expect($editada->unidad)->toBe('UF');
    expect($editada->orden)->toBe(3);

    $servicio->eliminarMetrica($editada);
    expect($ejecucion->metricas()->count())->toBe(0);
});

test('editarGrafico actualiza los campos y eliminarGrafico lo borra', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = $servicio->iniciarEjecucion(
        definicionInformeRazonadoDePrueba(),
        corteReportabilidadDePrueba(['estado' => 'publicado']),
    );

    $grafico = $servicio->agregarGrafico($ejecucion, 'GRA-1', 'Evolución', 'barra', ['series' => [1, 2]]);

    $editado = $servicio->editarGrafico($grafico, 'Evolución mensual', 'linea', ['series' => [3, 4, 5]], 2);
    expect($editado->titulo)->toBe('Evolución mensual');
    expect($editado->tipo)->toBe('linea');
    expect($editado->datos)->toBe(['series' => [3, 4, 5]]);
    expect($editado->orden)->toBe(2);

    $servicio->eliminarGrafico($editado);
    expect($ejecucion->graficos()->count())->toBe(0);
});

test('ExportadorInformeRazonadoService genera archivos HTML y PDF y rechaza formatos no soportados', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    Storage::fake('local');

    $servicio = app(InformeRazonadoService::class);
    $ejecucion = $servicio->iniciarEjecucion(
        definicionInformeRazonadoDePrueba(),
        corteReportabilidadDePrueba(['estado' => 'publicado']),
    );
    $servicio->agregarMetrica($ejecucion, 'MET-1', 'Monto total', 100.0, 'CLP');

    $exportador = app(ExportadorInformeRazonadoService::class);

    $ruta = $exportador->exportar($ejecucion, 'html');

    Storage::disk('local')->assertExists($ruta);
    expect($ruta)->toEndWith('.html');
    expect(Storage::disk('local')->get($ruta))->toContain('Monto total');

    $rutaPdf = $exportador->exportar($ejecucion, 'pdf');

    Storage::disk('local')->assertExists($rutaPdf);
    expect($rutaPdf)->toEndWith('.pdf');
    expect(Storage::disk('local')->get($rutaPdf))->toStartWith('%PDF-');

    expect(fn () => $exportador->exportar($ejecucion, 'docx'))
        ->toThrow(InvalidArgumentException::class);
});
