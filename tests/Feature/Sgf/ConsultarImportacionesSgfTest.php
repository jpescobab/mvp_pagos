<?php

use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
});

test('un usuario autenticado puede listar las importaciones SGF ordenadas de la más reciente a la más antigua', function () {
    $anterior = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now()->subDay(),
        'finalizado_en' => now()->subDay(),
        'total_elementos' => 3,
        'estado' => 'completado',
    ]);
    $reciente = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'verificar_caso',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'en_progreso',
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('sgf/importaciones/index')
        ->has('importaciones.data', 2)
        ->where('importaciones.data.0.id', $reciente->id)
        ->where('importaciones.data.1.id', $anterior->id)
        ->where('importaciones.data.1.total_elementos', 3)
        ->where('importaciones.data.1.estado', 'completado')
    );
});

test('el detalle de una importación incluye los snapshots que produjo', function () {
    $trabajo = TrabajoIntegracion::create([
        'sistema_externo_id' => $this->sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'estado' => 'en_progreso',
    ]);

    SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-importacion-1',
        'payload_crudo' => ['estado' => 'EN_TRAMITE'],
        'payload_normalizado' => ['estado' => 'EN_TRAMITE'],
        'hash' => 'hash-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $trabajo));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('sgf/importaciones/show')
        ->has('importacion.snapshots', 1)
        ->where('importacion.snapshots.0.referencia_externa', 'sgf-importacion-1')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('sgf.importaciones.index'));

    $response->assertRedirect(route('login'));
});
