<?php

use App\Models\ImportacionSgf;
use App\Models\SnapshotSgf;
use App\Models\User;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('el detalle de un caso de pago incluye el historial de snapshots SGF ordenado del más reciente al más antiguo', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-historial-1');

    $importacionAnterior = ImportacionSgf::create(['fuente' => 'manual', 'iniciado_en' => now()->subDay(), 'estado' => 'completada']);
    SnapshotSgf::create([
        'importacion_sgf_id' => $importacionAnterior->id,
        'sgf_id' => $caso->sgf_id,
        'payload_crudo' => ['estado' => 'EN_TRAMITE'],
        'payload_normalizado' => ['estado' => 'EN_TRAMITE'],
        'hash' => 'hash-anterior',
        'capturado_en' => now()->subDay(),
    ]);

    $importacionReciente = ImportacionSgf::create(['fuente' => 'manual', 'iniciado_en' => now(), 'estado' => 'completada']);
    SnapshotSgf::create([
        'importacion_sgf_id' => $importacionReciente->id,
        'sgf_id' => $caso->sgf_id,
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
