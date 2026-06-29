<?php

use App\Models\AuditLog;
use App\Models\RegistroContableCgu;
use App\Models\RegistroPagoBancario;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con el permiso registrar_cgu registra evidencia de registro contable CGU y queda auditado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_cgu');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.registros-contables-cgu.store', $caso),
        [
            'numero_registro' => 'CGU-2026-001',
            'fecha_registro' => '2026-06-28',
            'monto' => 500000,
            'observaciones' => 'Conciliado con factura 123',
        ],
    );

    $response->assertSessionHasNoErrors();

    $registro = RegistroContableCgu::where('caso_pago_proveedor_id', $caso->id)->first();
    expect($registro)->not->toBeNull();
    expect($registro->numero_registro)->toBe('CGU-2026-001');
    expect($registro->registrado_por)->toBe($usuario->id);

    $auditoria = AuditLog::where('action', 'caso_pago_proveedor.registrar_contable_cgu')->first();
    expect($auditoria)->not->toBeNull();
    expect($auditoria->user_id)->toBe($usuario->id);
});

test('un usuario sin el permiso registrar_cgu es bloqueado y queda auditado como acceso denegado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.registros-contables-cgu.store', $caso),
        ['numero_registro' => 'CGU-2026-001', 'fecha_registro' => '2026-06-28', 'monto' => 500000],
    );

    $response->assertForbidden();
    expect(RegistroContableCgu::where('caso_pago_proveedor_id', $caso->id)->exists())->toBeFalse();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('un usuario con el permiso pagar registra evidencia de pago bancario y queda auditado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.pagar');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.registros-pago-bancario.store', $caso),
        [
            'numero_operacion' => 'OP-2026-001',
            'fecha_pago' => '2026-06-28',
            'monto' => 500000,
            'banco' => 'BancoEstado',
        ],
    );

    $response->assertSessionHasNoErrors();

    $registro = RegistroPagoBancario::where('caso_pago_proveedor_id', $caso->id)->first();
    expect($registro)->not->toBeNull();
    expect($registro->numero_operacion)->toBe('OP-2026-001');
    expect($registro->banco)->toBe('BancoEstado');
    expect($registro->registrado_por)->toBe($usuario->id);

    $auditoria = AuditLog::where('action', 'caso_pago_proveedor.registrar_pago_bancario')->first();
    expect($auditoria)->not->toBeNull();
});

test('un usuario sin el permiso pagar es bloqueado y queda auditado como acceso denegado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.registros-pago-bancario.store', $caso),
        ['numero_operacion' => 'OP-2026-001', 'fecha_pago' => '2026-06-28', 'monto' => 500000],
    );

    $response->assertForbidden();
    expect(RegistroPagoBancario::where('caso_pago_proveedor_id', $caso->id)->exists())->toBeFalse();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('el detalle de un caso de pago incluye el historial completo de ambos registros', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();

    RegistroContableCgu::create([
        'caso_pago_proveedor_id' => $caso->id,
        'numero_registro' => 'CGU-1',
        'fecha_registro' => '2026-06-01',
        'monto' => 100000,
    ]);
    RegistroContableCgu::create([
        'caso_pago_proveedor_id' => $caso->id,
        'numero_registro' => 'CGU-2',
        'fecha_registro' => '2026-06-15',
        'monto' => 200000,
    ]);
    RegistroPagoBancario::create([
        'caso_pago_proveedor_id' => $caso->id,
        'numero_operacion' => 'OP-1',
        'fecha_pago' => '2026-06-20',
        'monto' => 300000,
        'banco' => 'BancoEstado',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/casos/show')
        ->has('caso.registros_contables_cgu', 2)
        ->has('caso.registros_pago_bancario', 1)
        ->where('caso.registros_pago_bancario.0.numero_operacion', 'OP-1')
    );
});
