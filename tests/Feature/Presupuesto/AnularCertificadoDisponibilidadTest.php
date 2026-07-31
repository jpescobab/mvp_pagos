<?php

use App\Exceptions\CertificadoDisponibilidadPresupuestariaException;
use App\Models\User;
use App\Services\Presupuesto\AnularCertificadoDisponibilidadService;
use App\Services\Presupuesto\CalculadorSaldoPresupuestoService;
use App\Services\Presupuesto\FirmarCertificadoDisponibilidadService;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPresupuestoCdpSeeder;

test('anular un cdp firmado crea un cdp nuevo con 100% del monto negativo referenciando el original', function () {
    (new TiposDocumentoSeeder)->run();
    (new WorkflowPresupuestoCdpSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['presupuesto.firmar_cdp', 'presupuesto.anular_cdp']);
    $this->actingAs($usuario);

    $cdp = crearCdpBorradorDePrueba();
    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    $anulacion = app(AnularCertificadoDisponibilidadService::class)->anular($firmado);

    expect((float) $anulacion->monto)->toBe(-100000.0);
    expect($anulacion->cdp_original_id)->toBe($firmado->id);
    expect($anulacion->requerimiento_numero)->toBe($firmado->requerimiento_numero);
    expect($anulacion->proceso->fresh()->estadoActual->codigo)->toBe('firmado');
});

test('anular no modifica el estado del cdp original', function () {
    (new TiposDocumentoSeeder)->run();
    (new WorkflowPresupuestoCdpSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['presupuesto.firmar_cdp', 'presupuesto.anular_cdp']);
    $this->actingAs($usuario);

    $cdp = crearCdpBorradorDePrueba();
    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    app(AnularCertificadoDisponibilidadService::class)->anular($firmado);

    expect($firmado->proceso->fresh()->estadoActual->codigo)->toBe('firmado');
    expect($firmado->fresh()->monto)->toEqual($firmado->monto);
});

test('el saldo neto tras anular vuelve al disponible previo al cdp original', function () {
    (new TiposDocumentoSeeder)->run();
    (new WorkflowPresupuestoCdpSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->givePermissionTo(['presupuesto.firmar_cdp', 'presupuesto.anular_cdp']);
    $this->actingAs($usuario);

    $presupuesto = crearLineaPresupuestoDePrueba();
    $saldoInicial = app(CalculadorSaldoPresupuestoService::class)->disponible($presupuesto);

    $cdp = crearCdpBorradorDePrueba($presupuesto);
    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    app(AnularCertificadoDisponibilidadService::class)->anular($firmado);

    $saldoFinal = app(CalculadorSaldoPresupuestoService::class)->disponible($presupuesto->fresh());

    expect($saldoFinal)->toBe($saldoInicial);
});

test('no puede anularse un cdp que no está firmado', function () {
    $cdp = crearCdpBorradorDePrueba();

    expect(fn () => app(AnularCertificadoDisponibilidadService::class)->anular($cdp))
        ->toThrow(CertificadoDisponibilidadPresupuestariaException::class);
});

test('un usuario sin presupuesto.anular_cdp no puede anular', function () {
    (new TiposDocumentoSeeder)->run();
    (new WorkflowPresupuestoCdpSeeder)->run();
    $usuarioFirma = User::factory()->create();
    $usuarioFirma->givePermissionTo('presupuesto.firmar_cdp');
    $this->actingAs($usuarioFirma);

    $cdp = crearCdpBorradorDePrueba();
    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    $usuarioSinPermiso = User::factory()->create();
    $response = $this->actingAs($usuarioSinPermiso)
        ->post(route('presupuesto.cdps.anular', $firmado));

    $response->assertForbidden();
});
