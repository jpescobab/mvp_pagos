<?php

use App\Models\AuditLog;
use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

function jurisdiccionParaAuditoria(): Jurisdiccion
{
    $institucion = Institucion::create(['codigo' => 'CAPJ-AUD', 'nombre' => 'CAPJ']);

    return Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
}

test('crear una tabla maestra deja un audit_log con la acción, la entidad y el usuario', function () {
    $jurisdiccion = jurisdiccionParaAuditoria();
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $cfinanciero = Cfinanciero::create([
        'jurisdiccion_id' => $jurisdiccion->id,
        'codigo' => '1400',
        'nombre' => 'Administración Zonal',
        'activo' => true,
    ]);

    $log = AuditLog::where('action', 'crear_cfinanciero')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($actor->id);
    expect($log->auditable_type)->toBe($cfinanciero->getMorphClass());
    expect($log->auditable_id)->toBe($cfinanciero->id);
    expect($log->before)->toBe([]);
    expect($log->after['codigo'])->toBe('1400');
    expect($log->after['nombre'])->toBe('Administración Zonal');
});

test('editar una tabla maestra audita solo los campos que cambiaron', function () {
    $jurisdiccion = jurisdiccionParaAuditoria();
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $cfinanciero = Cfinanciero::create([
        'jurisdiccion_id' => $jurisdiccion->id,
        'codigo' => '1400',
        'nombre' => 'Nombre Original',
        'activo' => true,
    ]);

    $cfinanciero->update(['nombre' => 'Nombre Corregido']);

    $log = AuditLog::where('action', 'editar_cfinanciero')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($actor->id);
    expect($log->before)->toBe(['nombre' => 'Nombre Original']);
    expect($log->after)->toBe(['nombre' => 'Nombre Corregido']);
});

test('eliminar una tabla maestra deja un audit_log con el estado anterior', function () {
    $jurisdiccion = jurisdiccionParaAuditoria();
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $cfinanciero = Cfinanciero::create([
        'jurisdiccion_id' => $jurisdiccion->id,
        'codigo' => '1400',
        'nombre' => 'A Eliminar',
        'activo' => true,
    ]);

    $cfinanciero->delete();

    $log = AuditLog::where('action', 'eliminar_cfinanciero')->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->auditable_id)->toBe($cfinanciero->id);
    expect($log->before['codigo'])->toBe('1400');
    expect($log->after)->toBe([]);
});

test('el soft delete de un modelo maestro se audita como eliminación', function () {
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $proveedor = Proveedor::create(['rutproveedor' => '76.111.222-3', 'nombre' => 'Proveedor Soft Delete']);

    $proveedor->delete();

    expect($proveedor->trashed())->toBeTrue();

    $log = AuditLog::where('action', 'eliminar_proveedor')->latest('id')->first();
    expect($log)->not->toBeNull();
    expect($log->auditable_id)->toBe($proveedor->id);
});

test('una mutación sin usuario autenticado no genera auditoría', function () {
    $jurisdiccion = jurisdiccionParaAuditoria();

    $antes = AuditLog::count();

    // Sin actingAs: contexto de seeder/consola.
    $cfinanciero = Cfinanciero::create([
        'jurisdiccion_id' => $jurisdiccion->id,
        'codigo' => '9999',
        'nombre' => 'Sembrado',
        'activo' => true,
    ]);
    $cfinanciero->update(['nombre' => 'Sembrado y editado']);
    $cfinanciero->delete();

    expect(AuditLog::count())->toBe($antes);
});

test('un update que no cambia nada no genera auditoría', function () {
    $jurisdiccion = jurisdiccionParaAuditoria();
    $actor = User::factory()->create();
    $this->actingAs($actor);

    $cfinanciero = Cfinanciero::create([
        'jurisdiccion_id' => $jurisdiccion->id,
        'codigo' => '1400',
        'nombre' => 'Sin Cambios',
        'activo' => true,
    ]);

    $ediciones = AuditLog::where('action', 'editar_cfinanciero')->count();

    $cfinanciero->update(['nombre' => 'Sin Cambios']);

    expect(AuditLog::where('action', 'editar_cfinanciero')->count())->toBe($ediciones);
});

test('el flujo real por controlador audita la creación', function () {
    $jurisdiccion = jurisdiccionParaAuditoria();
    $this->seed(RolesAndPermissionsSeeder::class);

    $actor = User::factory()->create();
    $actor->givePermissionTo('core_institucional.administrar');

    $this->actingAs($actor)->post(route('maestros.cfinancieros.store'), [
        'codigo' => '1500',
        'nombre' => 'Desde Controlador',
        'jurisdiccion_id' => $jurisdiccion->id,
    ])->assertRedirect();

    $cfinanciero = Cfinanciero::where('codigo', '1500')->firstOrFail();

    $log = AuditLog::where('action', 'crear_cfinanciero')
        ->where('auditable_id', $cfinanciero->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($actor->id);
});
