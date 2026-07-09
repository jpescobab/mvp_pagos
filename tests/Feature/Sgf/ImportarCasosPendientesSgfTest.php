<?php

use App\Jobs\ImportarCasosPendientesSgfJob;
use App\Models\ConectorAutomatizacionNavegador;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\TrabajoIntegracion;
use App\Models\User;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(IntegracionesSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

function autorizarConectorSgfParaImportacionMasiva(): void
{
    ConectorAutomatizacionNavegador::where('codigo', 'SGF_PLAYWRIGHT')->firstOrFail()->update([
        'activo' => true,
        'autorizado_por' => User::factory()->create()->id,
        'autorizado_en' => now(),
    ]);
}

test('un usuario con permiso dispara la importación masiva y se encola un único Job', function () {
    Queue::fake();
    autorizarConectorSgfParaImportacionMasiva();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-pendientes'));

    $trabajo = TrabajoIntegracion::sole();
    expect($trabajo->tipo)->toBe('importar_pendientes');
    expect($trabajo->mecanismo)->toBe('playwright');
    expect($trabajo->estado)->toBe('en_progreso');

    $response->assertRedirect(route('sgf.importaciones.show', $trabajo));
    Queue::assertPushed(ImportarCasosPendientesSgfJob::class, 1);
});

test('si ya hay una importación masiva en curso no se despacha un Job nuevo', function () {
    Queue::fake();
    autorizarConectorSgfParaImportacionMasiva();

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    $trabajoEnCurso = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now(),
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-pendientes'));

    $response->assertRedirect(route('sgf.importaciones.show', $trabajoEnCurso));
    expect(TrabajoIntegracion::count())->toBe(1);
    Queue::assertNotPushed(ImportarCasosPendientesSgfJob::class);
});

test('un trabajo huérfano (fuera de su umbral) no bloquea un nuevo intento y se marca automáticamente', function () {
    Queue::fake();
    autorizarConectorSgfParaImportacionMasiva();

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    $huerfano = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(91),
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-pendientes'));

    $nuevoTrabajo = TrabajoIntegracion::where('estado', 'en_progreso')->sole();
    $response->assertRedirect(route('sgf.importaciones.show', $nuevoTrabajo));
    expect($huerfano->refresh()->estado)->toBe('huerfano');
    Queue::assertPushed(ImportarCasosPendientesSgfJob::class, 1);
});

test('un trabajo en_progreso todavía dentro de su umbral sigue bloqueando el reintento', function () {
    Queue::fake();
    autorizarConectorSgfParaImportacionMasiva();

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    $trabajoEnCurso = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(10),
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-pendientes'));

    $response->assertRedirect(route('sgf.importaciones.show', $trabajoEnCurso));
    expect($trabajoEnCurso->refresh()->estado)->toBe('en_progreso');
    Queue::assertNotPushed(ImportarCasosPendientesSgfJob::class);
});

test('un usuario sin permiso no puede disparar la importación masiva', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-pendientes'));

    $response->assertForbidden();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('si el conector de SGF no está autorizado no se crea ningún trabajo_integracion', function () {
    Queue::fake();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $this->actingAs($usuario)->post(route('sgf.casos.importar-pendientes'));

    expect(TrabajoIntegracion::count())->toBe(0);
    Queue::assertNotPushed(ImportarCasosPendientesSgfJob::class);
});
