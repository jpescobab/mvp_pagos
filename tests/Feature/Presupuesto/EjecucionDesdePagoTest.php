<?php

// Reutiliza helpers de otros archivos de este directorio
// (crearLineaPresupuestoDePrueba, datosCdpDePrueba, crearProcesoAdquisicionDePruebaParaCdp):
// correr el directorio completo (php artisan test tests/Feature/Presupuesto).

use App\Models\AuditLog;
use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use App\Models\Presupuesto\MovimientoPresupuestario;
use App\Models\Presupuesto\Presupuesto;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Notifications\SobreEjecucionCdpNotification;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\Presupuesto\AnularCertificadoDisponibilidadService;
use App\Services\Presupuesto\CalculadorSaldoPresupuestoService;
use App\Services\Presupuesto\CrearBorradorCertificadoDisponibilidadService;
use App\Services\Presupuesto\FirmarCertificadoDisponibilidadService;
use App\Services\Presupuesto\RegistrarEjecucionDesdePagoService;
use App\Services\Workflow\TransicionWorkflowService;
use Database\Seeders\PresupuestoSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Database\Seeders\WorkflowPresupuestoCdpSeeder;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * @return array{usuario: User, presupuesto: Presupuesto}
 */
function prepararEscenarioEjecucionDesdePago(): array
{
    (new TiposDocumentoSeeder)->run();
    (new PresupuestoSeeder)->run();
    (new WorkflowPresupuestoCdpSeeder)->run();
    (new WorkflowPagoProveedoresSeeder)->run();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['presupuesto.firmar_cdp', 'presupuesto.anular_cdp', 'pago_proveedores.pagar']);
    test()->actingAs($usuario);

    return ['usuario' => $usuario, 'presupuesto' => crearLineaPresupuestoDePrueba()];
}

function crearCdpFirmadoDeAdquisicion(Presupuesto $presupuesto, ?int $procesoAdquisicionId, float $monto = 1000000): CertificadoDisponibilidadPresupuestaria
{
    $borrador = app(CrearBorradorCertificadoDisponibilidadService::class)->crear(
        datosCdpDePrueba($presupuesto, [
            'total_moneda_compra' => $monto,
            'proceso_adquisicion_id' => $procesoAdquisicionId,
        ]),
    );

    return app(FirmarCertificadoDisponibilidadService::class)->firmar($borrador);
}

function crearCasoListoParaPagarDePrueba(string $sgfId, float $monto, ?int $procesoAdquisicionId): CasoPagoProveedor
{
    $proveedor = Proveedor::create(['rutproveedor' => fake()->unique()->numerify('########-#'), 'nombre' => 'Proveedor de Prueba SpA']);
    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = ['sgf_id' => $sgfId, 'estado' => 'EN_TRAMITE', 'grupo_actual' => 'FINANZAS', 'observaciones' => null, 'rut' => $proveedor->rutproveedor, 'monto' => $monto];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $sgfId,
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
    $caso->update(['monto' => $monto, 'proceso_adquisicion_id' => $procesoAdquisicionId]);

    // Fixture: posiciona el proceso en "lista para pago" sin recorrer todo el flujo previo.
    $listaParaPago = EstadoWorkflow::where('definicion_workflow_id', $caso->proceso->definicion_workflow_id)
        ->where('codigo', 'lista_para_pago')->firstOrFail();
    $caso->proceso->update(['estado_actual_id' => $listaParaPago->id]);

    return $caso->refresh();
}

function pagarCasoDePrueba(CasoPagoProveedor $caso, User $usuario): void
{
    app(TransicionWorkflowService::class)->execute($caso->proceso, 'marcar_pagada_bancoestado', user: $usuario);
}

function movimientosDeCaso(CasoPagoProveedor $caso)
{
    return MovimientoPresupuestario::where('origen_type', $caso->getMorphClass())->where('origen_id', $caso->id);
}

test('pagar un caso con un único CDP vigente registra ejecución y liberación sin alterar el saldo disponible', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    $cdp = crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id, 1000000);
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-1', 400000, $adquisicion->id);

    $disponibleAntes = app(CalculadorSaldoPresupuestoService::class)->disponible($presupuesto->fresh());

    pagarCasoDePrueba($caso, $usuario);

    $movimientos = movimientosDeCaso($caso)->get();

    expect($movimientos->pluck('tipo')->sort()->values()->all())->toBe(['ejecucion', 'liberacion_compromiso'])
        ->and($movimientos->every(fn ($m) => (float) $m->monto === 400000.0))->toBeTrue()
        ->and($movimientos->every(fn ($m) => $m->certificado_disponibilidad_presupuestaria_id === $cdp->id))->toBeTrue()
        ->and($caso->proceso->fresh()->estadoActual->codigo)->toBe('pagada_bancoestado')
        ->and(app(CalculadorSaldoPresupuestoService::class)->disponible($presupuesto->fresh()))->toBe($disponibleAntes)
        ->and((float) $cdp->movimientosEjecucion()->sum('monto'))->toBe(400000.0);
});

test('pagos parciales de varios casos de la misma adquisición ejecutan cada uno su monto', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    $cdp = crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id, 1000000);

    pagarCasoDePrueba(crearCasoListoParaPagarDePrueba('sgf-ej-2a', 400000, $adquisicion->id), $usuario);
    pagarCasoDePrueba(crearCasoListoParaPagarDePrueba('sgf-ej-2b', 300000, $adquisicion->id), $usuario);

    $ejecutado = (float) $cdp->movimientosEjecucion()->sum('monto');

    expect($ejecutado)->toBe(700000.0)
        ->and((float) $cdp->monto - $ejecutado)->toBe(300000.0);
});

test('con el módulo Presupuesto desactivado el pago se completa sin registrar movimientos', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id);
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-3', 400000, $adquisicion->id);

    DefinicionWorkflow::where('codigo', 'presupuesto_cdp')->update(['activo' => false]);

    pagarCasoDePrueba($caso, $usuario);

    expect(movimientosDeCaso($caso)->count())->toBe(0)
        ->and($caso->proceso->fresh()->estadoActual->codigo)->toBe('pagada_bancoestado');
});

test('sin CDP vigente (solo anulados) no registra movimientos, deja constancia y no bloquea el pago', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    $cdp = crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id);
    app(AnularCertificadoDisponibilidadService::class)->anular($cdp);
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-4', 400000, $adquisicion->id);

    pagarCasoDePrueba($caso, $usuario);

    $constancia = AuditLog::where('action', 'presupuesto.ejecucion_no_resuelta')->sole();

    expect(movimientosDeCaso($caso)->count())->toBe(0)
        ->and($constancia->after['motivo'])->toContain('ningún CDP firmado vigente')
        ->and($caso->proceso->fresh()->estadoActual->codigo)->toBe('pagada_bancoestado');
});

test('con más de un CDP vigente no adivina: no registra y la constancia lista los candidatos', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    $cdpA = crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id, 200000);
    $cdpB = crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id, 300000);
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-5', 100000, $adquisicion->id);

    pagarCasoDePrueba($caso, $usuario);

    $constancia = AuditLog::where('action', 'presupuesto.ejecucion_no_resuelta')->sole();

    expect(movimientosDeCaso($caso)->count())->toBe(0)
        ->and(array_keys($constancia->after['cdp_candidatos']))->toEqualCanonicalizing([$cdpA->id, $cdpB->id]);
});

test('un caso sin adquisición vinculada no registra ejecución ni constancia', function () {
    ['usuario' => $usuario] = prepararEscenarioEjecucionDesdePago();
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-6', 400000, null);

    pagarCasoDePrueba($caso, $usuario);

    expect(movimientosDeCaso($caso)->count())->toBe(0)
        ->and(AuditLog::where('action', 'presupuesto.ejecucion_no_resuelta')->count())->toBe(0);
});

test('registrar dos veces para el mismo caso no duplica los movimientos', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id);
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-7', 400000, $adquisicion->id);
    $servicio = app(RegistrarEjecucionDesdePagoService::class);

    expect($servicio->registrar($caso, $usuario))->toBe(RegistrarEjecucionDesdePagoService::REGISTRADA)
        ->and($servicio->registrar($caso, $usuario))->toBe(RegistrarEjecucionDesdePagoService::YA_REGISTRADA)
        ->and(movimientosDeCaso($caso)->count())->toBe(2);
});

test('el índice único de la base impide un segundo movimiento del mismo tipo para el mismo origen', function () {
    ['presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-8', 400000, null);

    $datos = ['presupuesto_id' => $presupuesto->id, 'tipo' => 'ejecucion', 'monto' => 1, 'origen_type' => $caso->getMorphClass(), 'origen_id' => $caso->id];
    MovimientoPresupuestario::create($datos);

    expect(fn () => MovimientoPresupuestario::create($datos))->toThrow(UniqueConstraintViolationException::class);
});

test('la sobre-ejecución audita, notifica a quienes firman CDP y no bloquea el pago', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    $cdp = crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id, 500000);
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-9', 700000, $adquisicion->id);

    pagarCasoDePrueba($caso, $usuario);

    $alerta = AuditLog::where('action', 'presupuesto.sobre_ejecucion')->sole();

    expect(movimientosDeCaso($caso)->count())->toBe(2)
        ->and((float) $alerta->after['monto_ejecutado'])->toBe(700000.0)
        ->and($alerta->auditable_id)->toBe($cdp->id)
        ->and($usuario->notifications()->where('type', SobreEjecucionCdpNotification::class)->count())->toBe(1)
        ->and($caso->proceso->fresh()->estadoActual->codigo)->toBe('pagada_bancoestado');
});

test('una falla al registrar la ejecución no revierte el pago y queda en auditoría', function () {
    ['usuario' => $usuario] = prepararEscenarioEjecucionDesdePago();
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-10', 400000, null);

    $this->mock(RegistrarEjecucionDesdePagoService::class, function ($mock) {
        $mock->shouldReceive('registrar')->andThrow(new RuntimeException('falla simulada'));
    });

    pagarCasoDePrueba($caso, $usuario);

    expect($caso->proceso->fresh()->estadoActual->codigo)->toBe('pagada_bancoestado')
        ->and(AuditLog::where('action', 'presupuesto.ejecucion_fallida')->sole()->after['error'])->toBe('falla simulada');
});

test('otras transiciones del caso no registran ejecución', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id);
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-11', 400000, $adquisicion->id);
    $usuario->givePermissionTo('pago_proveedores.gestionar_caso');

    pagarCasoDePrueba($caso, $usuario);
    movimientosDeCaso($caso)->delete();

    app(TransicionWorkflowService::class)->execute($caso->proceso->fresh(), 'asociar_egreso_cgu', user: $usuario);

    expect(movimientosDeCaso($caso)->count())->toBe(0);
});

test('el detalle del CDP entrega la ejecución con pagos, ejecutado, remanente y sobre-ejecución', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $adquisicion = crearProcesoAdquisicionDePruebaParaCdp();
    $cdp = crearCdpFirmadoDeAdquisicion($presupuesto, $adquisicion->id, 500000);
    $usuario->givePermissionTo('presupuesto.consultar');
    $caso = crearCasoListoParaPagarDePrueba('sgf-ej-12', 700000, $adquisicion->id);
    pagarCasoDePrueba($caso, $usuario);

    $this->actingAs($usuario)->get(route('presupuesto.cdps.show', $cdp))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cdp.ejecucion.monto_ejecutado', 700000)
            ->where('cdp.ejecucion.compromiso_remanente', 0)
            ->where('cdp.ejecucion.sobre_ejecutado', true)
            ->has('cdp.ejecucion.pagos', 1)
            ->where('cdp.ejecucion.pagos.0.caso_id', $caso->id)
            ->where('cdp.ejecucion.pagos.0.sgf_id', 'sgf-ej-12'));
});

test('un CDP firmado sin pagos muestra ejecutado cero y remanente igual al monto', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $cdp = crearCdpFirmadoDeAdquisicion($presupuesto, null, 800000);
    $usuario->givePermissionTo('presupuesto.consultar');

    $this->actingAs($usuario)->get(route('presupuesto.cdps.show', $cdp))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cdp.ejecucion.monto_ejecutado', 0)
            ->where('cdp.ejecucion.compromiso_remanente', 800000)
            ->where('cdp.ejecucion.sobre_ejecutado', false)
            ->has('cdp.ejecucion.pagos', 0));
});

test('un CDP en borrador o de anulación (monto negativo) no expone ejecución', function () {
    ['usuario' => $usuario, 'presupuesto' => $presupuesto] = prepararEscenarioEjecucionDesdePago();
    $usuario->givePermissionTo('presupuesto.consultar');
    $borrador = app(CrearBorradorCertificadoDisponibilidadService::class)->crear(datosCdpDePrueba($presupuesto));
    $firmado = crearCdpFirmadoDeAdquisicion($presupuesto, null, 100000);
    $anulacion = app(AnularCertificadoDisponibilidadService::class)->anular($firmado);

    $this->actingAs($usuario)->get(route('presupuesto.cdps.show', $borrador))
        ->assertOk()->assertInertia(fn ($page) => $page->where('cdp.ejecucion', null));

    $this->actingAs($usuario)->get(route('presupuesto.cdps.show', $anulacion))
        ->assertOk()->assertInertia(fn ($page) => $page->where('cdp.ejecucion', null));
});
