<?php

use App\Models\EgresoCgu;
use App\Models\User;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('el detalle de un caso de pago incluye los egresos CGU asociados con el monto del item', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-egreso-1');

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-2026-001',
        'fecha' => '2026-06-28',
        'monto_total' => 450000,
    ]);
    $egreso->items()->create([
        'caso_pago_proveedor_id' => $caso->id,
        'monto' => 450000,
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/casos/show')
        ->has('caso.egresos_cgu', 1)
        ->where('caso.egresos_cgu.0.numero_egreso', 'EGR-2026-001')
        ->where('caso.egresos_cgu.0.monto', '450000.00')
    );
});

test('el detalle de un caso de pago sin egresos asociados devuelve una lista vacía', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-egreso-2');
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/casos/show')
        ->has('caso.egresos_cgu', 0)
    );
});
