<?php

use App\Models\AuditLog;
use App\Models\Factura;
use App\Models\SecurityAuditLog;
use App\Models\User;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario con el permiso registrar_factura registra una factura y queda auditado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_factura');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.facturas.store', $caso),
        [
            'folio' => 'F-2026-001',
            'monto' => 500000,
            'fecha_emision' => '2026-06-28',
        ],
    );

    $response->assertSessionHasNoErrors();

    $factura = Factura::where('caso_pago_proveedor_id', $caso->id)->first();
    expect($factura)->not->toBeNull();
    expect($factura->folio)->toBe('F-2026-001');
    expect($factura->proveedor_id)->toBe($caso->proveedor_id);

    $auditoria = AuditLog::where('action', 'caso_pago_proveedor.registrar_factura')->first();
    expect($auditoria)->not->toBeNull();
    expect($auditoria->user_id)->toBe($usuario->id);
});

test('un usuario sin el permiso registrar_factura es bloqueado y queda auditado como acceso denegado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.casos.facturas.store', $caso),
        ['folio' => 'F-2026-001', 'monto' => 500000, 'fecha_emision' => '2026-06-28'],
    );

    $response->assertForbidden();
    expect(Factura::where('caso_pago_proveedor_id', $caso->id)->exists())->toBeFalse();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('el detalle de un caso de pago incluye todas las facturas registradas', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba();

    Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'folio' => 'F-1',
        'monto' => 100000,
        'fecha_emision' => '2026-06-01',
    ]);
    Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'folio' => 'F-2',
        'monto' => 200000,
        'fecha_emision' => '2026-06-15',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/casos/show')
        ->has('caso.facturas', 2)
        ->where('caso.facturas.0.folio', 'F-1')
    );
});
