<?php

use App\Models\SecurityAuditLog;
use App\Models\User;
use App\Services\Presupuesto\FirmarCertificadoDisponibilidadService;
use Database\Seeders\PresupuestoSeeder;
use Database\Seeders\TiposDocumentoSeeder;

function usuarioConPermisoConsultarCdp(): User
{
    (new PresupuestoSeeder)->run();
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('presupuesto.consultar');

    return $usuario;
}

test('un usuario con presupuesto.consultar ve el listado de cdp', function () {
    crearCdpBorradorDePrueba();
    $usuario = usuarioConPermisoConsultarCdp();

    $response = $this->actingAs($usuario)->get(route('presupuesto.cdps.index'));

    $response->assertOk();
});

test('un usuario con presupuesto.consultar ve el detalle de un cdp', function () {
    $cdp = crearCdpBorradorDePrueba();
    $usuario = usuarioConPermisoConsultarCdp();

    $response = $this->actingAs($usuario)->get(route('presupuesto.cdps.show', $cdp));

    $response->assertOk();
});

test('el pdf solo está disponible una vez firmado el cdp', function () {
    (new TiposDocumentoSeeder)->run();
    (new PresupuestoSeeder)->run();
    $cdp = crearCdpBorradorDePrueba();

    $usuarioFirma = User::factory()->create();
    $usuarioFirma->givePermissionTo(['presupuesto.firmar_cdp', 'presupuesto.consultar']);
    $this->actingAs($usuarioFirma);

    $responseBorrador = $this->get(route('presupuesto.cdps.show', $cdp));
    $responseBorrador->assertOk();
    expect($responseBorrador->inertiaPage()['props']['cdp']['proceso']['documentos'] ?? [])->toBeEmpty();

    $firmado = app(FirmarCertificadoDisponibilidadService::class)->firmar($cdp);

    $responseFirmado = $this->get(route('presupuesto.cdps.show', $firmado));
    $responseFirmado->assertOk();
    $documentos = $responseFirmado->inertiaPage()['props']['cdp']['proceso']['documentos'] ?? [];
    expect($documentos)->toHaveCount(1);
    expect($documentos[0]['tipo_documento'])->toBe('Certificado de Disponibilidad Presupuestaria');
});

test('un usuario sin presupuesto.consultar no puede ver el listado ni el detalle y queda auditado', function () {
    $cdp = crearCdpBorradorDePrueba();
    $usuario = User::factory()->create();

    $responseIndex = $this->actingAs($usuario)->get(route('presupuesto.cdps.index'));
    $responseIndex->assertForbidden();

    $responseShow = $this->actingAs($usuario)->get(route('presupuesto.cdps.show', $cdp));
    $responseShow->assertForbidden();

    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});
