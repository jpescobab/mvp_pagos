<?php

use App\Exceptions\TransicionWorkflowException;
use App\Models\Documento;
use App\Models\Presupuesto\MovimientoPresupuestario;
use App\Models\User;
use App\Services\Presupuesto\FirmarCertificadoDisponibilidadService;
use Database\Seeders\TiposDocumentoSeeder;
use Database\Seeders\WorkflowPresupuestoCdpSeeder;

function usuarioConPermisoFirmarCdp(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('presupuesto.firmar_cdp');

    return $usuario;
}

test('firmar con saldo suficiente transiciona el proceso, compromete saldo y genera el documento', function () {
    (new TiposDocumentoSeeder)->run();
    $cdp = crearCdpBorradorDePrueba();
    $usuario = usuarioConPermisoFirmarCdp();
    $this->actingAs($usuario);

    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    expect($firmado->proceso->fresh()->estadoActual->codigo)->toBe('firmado');
    expect($firmado->proceso->fresh()->historialTransiciones()->count())->toBe(1);
    expect($firmado->firmado_por)->toBe($usuario->id);
    expect($firmado->firmado_en)->not->toBeNull();
    expect($firmado->hubo_sobregiro_al_emitir)->toBeFalse();

    $movimiento = MovimientoPresupuestario::first();
    expect($movimiento->tipo)->toBe('compromiso');
    expect((float) $movimiento->monto)->toBe(100000.0);
    expect($movimiento->origen_type)->toBe($firmado::class);
    expect($movimiento->origen_id)->toBe($firmado->id);

    $documento = Documento::first();
    expect($documento)->not->toBeNull();
    expect($documento->tipoDocumento->codigo)->toBe('CDP');
    expect($firmado->proceso->vinculosDocumento()->where('documento_id', $documento->id)->exists())->toBeTrue();
});

test('firmar con monto mayor al saldo disponible no bloquea pero marca el sobregiro', function () {
    (new TiposDocumentoSeeder)->run();
    $presupuesto = crearLineaPresupuestoDePrueba(['monto_asignado' => 50000]);
    $cdp = crearCdpBorradorDePrueba($presupuesto, ['monto' => 100000, 'total_moneda_compra' => 100000]);
    $this->actingAs(usuarioConPermisoFirmarCdp());

    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    expect($firmado->proceso->fresh()->estadoActual->codigo)->toBe('firmado');
    expect($firmado->hubo_sobregiro_al_emitir)->toBeTrue();
    expect((float) $firmado->saldo_disponible_al_emitir)->toBe(50000.0);
});

test('un cdp firmado no ofrece transiciones de salida', function () {
    (new TiposDocumentoSeeder)->run();
    $cdp = crearCdpBorradorDePrueba();
    $this->actingAs(usuarioConPermisoFirmarCdp());

    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    $transicionesDisponibles = $firmado->proceso->definicionWorkflow->transiciones()
        ->where('estado_origen_id', $firmado->proceso->fresh()->estado_actual_id)
        ->count();

    expect($transicionesDisponibles)->toBe(0);
});

test('un usuario sin presupuesto.firmar_cdp no puede firmar', function () {
    (new WorkflowPresupuestoCdpSeeder)->run();
    $cdp = crearCdpBorradorDePrueba();
    $usuarioSinPermiso = User::factory()->create();

    expect(fn () => app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp))
        ->toThrow(TransicionWorkflowException::class);

    $this->actingAs($usuarioSinPermiso);
    $response = $this->post(route('presupuesto.cdps.transiciones.store', $cdp), ['codigo' => 'firmar']);

    $response->assertRedirect();
    expect($cdp->proceso->fresh()->estadoActual->codigo)->toBe('borrador');
});
