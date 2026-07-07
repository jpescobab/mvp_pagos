<?php

use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('el detalle de un caso de pago incluye el historial de snapshots SGF ordenado del más reciente al más antiguo', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-historial-1');

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $caso->sgf_id,
        'payload_crudo' => ['estado' => 'EN_TRAMITE'],
        'payload_normalizado' => ['estado' => 'EN_TRAMITE'],
        'hash' => 'hash-anterior',
        'capturado_en' => now()->subDay(),
    ]);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $caso->sgf_id,
        'payload_crudo' => ['estado' => 'PAGADO'],
        'payload_normalizado' => ['estado' => 'PAGADO'],
        'hash' => 'hash-reciente',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.casos.show', $caso));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/casos/show')
        ->has('caso.snapshots_sgf', 3)
        ->where('caso.snapshots_sgf.0.hash', 'hash-reciente')
        ->where('caso.snapshots_sgf.1.hash', 'hash-anterior')
    );
});
