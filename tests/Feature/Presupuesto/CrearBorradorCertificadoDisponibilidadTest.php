<?php

use App\Models\Catalogo;
use App\Models\Cfinanciero;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Models\Presupuesto\MovimientoPresupuestario;
use App\Models\Presupuesto\PlanTarea;
use App\Models\Presupuesto\Presupuesto;
use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Services\Presupuesto\CrearBorradorCertificadoDisponibilidadService;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\CfinancierosSeeder;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\JurisdiccionesSeeder;
use Database\Seeders\WorkflowPresupuestoCdpSeeder;

function crearLineaPresupuestoDePrueba(array $overrides = []): Presupuesto
{
    (new JurisdiccionesSeeder)->run();
    (new CfinancierosSeeder)->run();
    (new ItemsSeeder)->run();
    (new CatalogosSeeder)->run();

    $cfinanciero = Cfinanciero::where('codigo', '1400')->firstOrFail();
    $catalogo = Catalogo::where('codigo', '2208001001')->firstOrFail();
    $planTarea = PlanTarea::firstOrCreate(['codigo' => 'PL1'], ['nombre' => 'Plan de tarea de prueba']);

    return Presupuesto::create(array_merge([
        'cfinanciero_id' => $cfinanciero->id,
        'catalogo_id' => $catalogo->id,
        'plan_tarea_id' => $planTarea->id,
        'anio' => 2026,
        'monto_asignado' => 1000000,
    ], $overrides));
}

/**
 * @param  array<string, mixed>  $overrides
 */
function datosCdpDePrueba(Presupuesto $presupuesto, array $overrides = []): array
{
    return array_merge([
        'presupuesto_id' => $presupuesto->id,
        'tipo_gasto' => 'GO',
        'codigo_iniciativa' => null,
        'nombre' => 'Compra de prueba',
        'programa_presupuestario' => '100',
        'caracter_gasto' => 'transitorio',
        'moneda_compra' => 'CLP',
        'total_moneda_compra' => 100000,
        'paridad' => null,
        'monto' => 100000,
        'anio_validez' => 2026,
        'requerimiento_numero' => '1000',
    ], $overrides);
}

function crearCdpBorradorDePrueba(?Presupuesto $presupuesto = null, array $overrides = []): CertificadoDisponibilidadPresupuestaria
{
    (new WorkflowPresupuestoCdpSeeder)->run();
    $presupuesto ??= crearLineaPresupuestoDePrueba();

    return app(CrearBorradorCertificadoDisponibilidadService::class)
        ->crear(datosCdpDePrueba($presupuesto, $overrides));
}

test('crear un borrador asigna folio, no compromete saldo y no crea movimiento', function () {
    $cdp = crearCdpBorradorDePrueba();

    expect($cdp->folio)->toMatch('/^CDP \d{3}-2026$/');
    expect($cdp->proceso->estadoActual->codigo)->toBe('borrador');
    expect(MovimientoPresupuestario::count())->toBe(0);
});

test('el folio es correlativo dentro del mismo año', function () {
    $presupuesto = crearLineaPresupuestoDePrueba();

    $primero = crearCdpBorradorDePrueba($presupuesto);
    $segundo = crearCdpBorradorDePrueba($presupuesto);

    expect($primero->folio)->toBe('CDP 001-2026');
    expect($segundo->folio)->toBe('CDP 002-2026');
});

test('el correlativo es global y no se reinicia entre años distintos', function () {
    $presupuesto = crearLineaPresupuestoDePrueba();

    $anioAnterior = crearCdpBorradorDePrueba($presupuesto, ['anio_validez' => 2025]);
    $anioActual = crearCdpBorradorDePrueba($presupuesto, ['anio_validez' => 2026]);
    $otroAnioActual = crearCdpBorradorDePrueba($presupuesto, ['anio_validez' => 2026]);

    expect($anioAnterior->folio)->toBe('CDP 001-2025');
    expect($anioActual->folio)->toBe('CDP 002-2026');
    expect($otroAnioActual->folio)->toBe('CDP 003-2026');
});

test('un cdp en borrador puede editarse', function () {
    $cdp = crearCdpBorradorDePrueba();

    $actualizado = app(CrearBorradorCertificadoDisponibilidadService::class)
        ->actualizar($cdp, datosCdpDePrueba($cdp->presupuesto, ['nombre' => 'Nombre editado']));

    expect($actualizado->nombre)->toBe('Nombre editado');
});

test('un usuario sin presupuesto.crear_cdp no puede crear un cdp y queda auditado', function () {
    (new WorkflowPresupuestoCdpSeeder)->run();
    $presupuesto = crearLineaPresupuestoDePrueba();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('presupuesto.cdps.store'), datosCdpDePrueba($presupuesto));

    $response->assertForbidden();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});
