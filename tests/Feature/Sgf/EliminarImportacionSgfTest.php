<?php

use App\Models\AuditLog;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Spatie\Permission\Models\Permission;

function crearCorridaSgf(int $sistemaId, string $estado): TrabajoIntegracion
{
    return TrabajoIntegracion::create([
        'sistema_externo_id' => $sistemaId,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'iniciado_en' => now(),
        'finalizado_en' => $estado === 'en_progreso' ? null : now(),
        'estado' => $estado,
    ]);
}

function usuarioConPermisoEliminarImportacion(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('integraciones_sgf.eliminar_importacion');

    return $usuario->refresh();
}

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    Permission::findOrCreate('integraciones_sgf.eliminar_importacion');
    $this->sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
});

test('elimina una corrida en error sin snapshots y registra la eliminación en auditoría', function () {
    $trabajo = crearCorridaSgf($this->sistema->id, 'error');

    $usuario = usuarioConPermisoEliminarImportacion();

    $response = $this->actingAs($usuario)->delete(route('sgf.importaciones.destroy', $trabajo));

    $response->assertRedirect(route('sgf.importaciones.index'));

    expect(TrabajoIntegracion::find($trabajo->id))->toBeNull();
    expect(
        AuditLog::where('action', 'importaciones_sgf.eliminada')->where('user_id', $usuario->id)->exists()
    )->toBeTrue();
});

test('elimina una corrida que produjo snapshots sin tocar su trazabilidad', function () {
    $trabajo = crearCorridaSgf($this->sistema->id, 'completado');

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $this->sistema->id,
        'trabajo_integracion_id' => $trabajo->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => 'sgf-con-trazabilidad',
        'payload_crudo' => [],
        'payload_normalizado' => ['monto' => 100000],
        'hash' => 'hash-trazabilidad',
        'capturado_en' => now(),
    ]);

    $usuario = usuarioConPermisoEliminarImportacion();

    $response = $this->actingAs($usuario)->delete(route('sgf.importaciones.destroy', $trabajo));

    $response->assertRedirect(route('sgf.importaciones.index'));

    expect(TrabajoIntegracion::find($trabajo->id))->toBeNull();

    // El snapshot sobrevive intacto (payload, hash, fecha): solo pierde la
    // referencia a qué corrida en particular lo generó (nullOnDelete).
    $snapshot = $snapshot->refresh();
    expect($snapshot)->not->toBeNull();
    expect($snapshot->trabajo_integracion_id)->toBeNull();
    expect($snapshot->payload_normalizado)->toBe(['monto' => 100000]);
});

test('no elimina una corrida en progreso', function () {
    $trabajo = crearCorridaSgf($this->sistema->id, 'en_progreso');

    $usuario = usuarioConPermisoEliminarImportacion();

    $response = $this->actingAs($usuario)->delete(route('sgf.importaciones.destroy', $trabajo));

    $response->assertRedirect();

    expect(TrabajoIntegracion::find($trabajo->id))->not->toBeNull();
});

test('un usuario sin el permiso no puede eliminar una corrida', function () {
    $trabajo = crearCorridaSgf($this->sistema->id, 'error');

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->delete(route('sgf.importaciones.destroy', $trabajo));

    $response->assertForbidden();

    expect(TrabajoIntegracion::find($trabajo->id))->not->toBeNull();
});
