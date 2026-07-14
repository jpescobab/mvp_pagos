<?php

use App\Models\ConectorAutomatizacionNavegador;
use App\Models\SecurityAuditLog;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    config(['services.sgf_playwright.base_url' => 'http://sgf-playwright.test', 'services.sgf_playwright.api_key' => 'test-key']);
});

test('un usuario con permiso verifica un caso en SGF y ve el resultado en la página', function () {
    ConectorAutomatizacionNavegador::where('codigo', 'SGF_PLAYWRIGHT')->firstOrFail()->update([
        'activo' => true,
        'autorizado_por' => User::factory()->create()->id,
        'autorizado_en' => now(),
    ]);

    Http::fake(['*/casos/verificar' => Http::response([
        'encontrada' => true,
        'payload_crudo' => ['sgf_id' => 'sgf-verificar-1', 'estado' => 'EN_TRAMITE', 'rut' => '11111111-1', 'monto' => '100.000'],
        'pasos' => [],
    ], 200)]);

    $caso = crearCasoPagoProveedorDePrueba('sgf-verificar-1');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.verificar_caso_sgf');

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.casos.verificar-sgf', $caso));

    $response->assertRedirect(route('pago-proveedores.casos.show', $caso));
    $response->assertInertiaFlash('verificacionSgf.encontrada', true);
});

test('un usuario sin permiso no puede verificar un caso en SGF', function () {
    $caso = crearCasoPagoProveedorDePrueba('sgf-verificar-2');
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.casos.verificar-sgf', $caso));

    $response->assertForbidden();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('si el conector de SGF no está autorizado la verificación no crea ningún trabajo_integracion', function () {
    $caso = crearCasoPagoProveedorDePrueba('sgf-verificar-3');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.verificar_caso_sgf');

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.casos.verificar-sgf', $caso));

    $response->assertRedirect();
    expect(TrabajoIntegracion::count())->toBe(0);
});
