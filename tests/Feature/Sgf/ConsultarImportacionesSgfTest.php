<?php

use App\Models\ImportacionSgf;
use App\Models\SnapshotSgf;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('un usuario autenticado puede listar las importaciones SGF ordenadas de la más reciente a la más antigua', function () {
    $anterior = ImportacionSgf::create(['fuente' => 'manual', 'iniciado_en' => now()->subDay(), 'finalizado_en' => now()->subDay(), 'total_filas' => 3, 'estado' => 'completado']);
    $reciente = ImportacionSgf::create(['fuente' => 'manual', 'iniciado_en' => now(), 'estado' => 'en_progreso']);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.index'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('sgf/importaciones/index')
        ->has('importaciones.data', 2)
        ->where('importaciones.data.0.id', $reciente->id)
        ->where('importaciones.data.1.id', $anterior->id)
        ->where('importaciones.data.1.total_filas', 3)
        ->where('importaciones.data.1.estado', 'completado')
    );
});

test('el detalle de una importación incluye los snapshots que produjo', function () {
    $importacion = ImportacionSgf::create(['fuente' => 'manual', 'iniciado_en' => now(), 'estado' => 'en_progreso']);

    SnapshotSgf::create([
        'importacion_sgf_id' => $importacion->id,
        'sgf_id' => 'sgf-importacion-1',
        'payload_crudo' => ['estado' => 'EN_TRAMITE'],
        'payload_normalizado' => ['estado' => 'EN_TRAMITE'],
        'hash' => 'hash-1',
        'capturado_en' => now(),
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('sgf.importaciones.show', $importacion));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('sgf/importaciones/show')
        ->has('importacion.snapshots', 1)
        ->where('importacion.snapshots.0.sgf_id', 'sgf-importacion-1')
    );
});

test('un usuario no autenticado es redirigido al login', function () {
    $response = $this->get(route('sgf.importaciones.index'));

    $response->assertRedirect(route('login'));
});
