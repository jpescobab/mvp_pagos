<?php

use App\Models\AuditLog;
use App\Models\DefinicionInformeRazonado;
use App\Models\User;

test('crear, editar y eliminar una definición deja auditoría con el usuario y el diff', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-AUD', 'nombre' => 'Nombre Original']);

    $creacion = AuditLog::where('action', 'crear_definicion_informe_razonado')->latest('id')->first();
    expect($creacion)->not->toBeNull();
    expect($creacion->user_id)->toBe($actor->id);
    expect($creacion->auditable_id)->toBe($definicion->id);
    expect($creacion->after['codigo'])->toBe('INF-AUD');

    $definicion->update(['nombre' => 'Nombre Corregido']);

    $edicion = AuditLog::where('action', 'editar_definicion_informe_razonado')->latest('id')->first();
    expect($edicion->before)->toBe(['nombre' => 'Nombre Original']);
    expect($edicion->after)->toBe(['nombre' => 'Nombre Corregido']);

    $definicion->delete();

    $eliminacion = AuditLog::where('action', 'eliminar_definicion_informe_razonado')->latest('id')->first();
    expect($eliminacion)->not->toBeNull();
    expect($eliminacion->before['codigo'])->toBe('INF-AUD');
});

test('sembrar una definición sin usuario autenticado no genera auditoría', function () {
    $antes = AuditLog::count();

    // Sin actingAs: contexto de seeder/consola.
    $definicion = DefinicionInformeRazonado::create(['codigo' => 'INF-SEED', 'nombre' => 'Sembrada']);
    $definicion->update(['nombre' => 'Sembrada y editada']);
    $definicion->delete();

    expect(AuditLog::count())->toBe($antes);
});
