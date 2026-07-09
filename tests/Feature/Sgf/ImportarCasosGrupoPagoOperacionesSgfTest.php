<?php

use App\Jobs\ImportarCasosGrupoPagoOperacionesSgfJob;
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

function autorizarConectorSgfParaGrupoPagoOperaciones(): void
{
    ConectorAutomatizacionNavegador::where('codigo', 'SGF_PLAYWRIGHT')->firstOrFail()->update([
        'activo' => true,
        'autorizado_por' => User::factory()->create()->id,
        'autorizado_en' => now(),
    ]);
}

test('un usuario con permiso dispara la importación del grupo Pago Operaciones y se encola un único Job', function () {
    Queue::fake();
    autorizarConectorSgfParaGrupoPagoOperaciones();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-grupo-pago-operaciones'));

    $trabajo = TrabajoIntegracion::sole();
    expect($trabajo->tipo)->toBe('importar_grupo_pago_operaciones');
    expect($trabajo->mecanismo)->toBe('playwright');
    expect($trabajo->estado)->toBe('en_progreso');

    $response->assertRedirect(route('sgf.importaciones.show', $trabajo));
    Queue::assertPushed(ImportarCasosGrupoPagoOperacionesSgfJob::class, 1);
});

test('si ya hay una importación del grupo Pago Operaciones en curso no se despacha un Job nuevo', function () {
    Queue::fake();
    autorizarConectorSgfParaGrupoPagoOperaciones();

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    $trabajoEnCurso = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now(),
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-grupo-pago-operaciones'));

    $response->assertRedirect(route('sgf.importaciones.show', $trabajoEnCurso));
    expect(TrabajoIntegracion::count())->toBe(1);
    Queue::assertNotPushed(ImportarCasosGrupoPagoOperacionesSgfJob::class);
});

test('una importación masiva en curso no bloquea la importación del grupo Pago Operaciones', function () {
    Queue::fake();
    autorizarConectorSgfParaGrupoPagoOperaciones();

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_pendientes',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now(),
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $this->actingAs($usuario)->post(route('sgf.casos.importar-grupo-pago-operaciones'));

    Queue::assertPushed(ImportarCasosGrupoPagoOperacionesSgfJob::class, 1);
});

test('un trabajo huérfano de este grupo (fuera de su umbral) no bloquea un nuevo intento y se marca automáticamente', function () {
    Queue::fake();
    autorizarConectorSgfParaGrupoPagoOperaciones();

    $sistema = SistemaExterno::where('codigo', 'SGF')->firstOrFail();
    $huerfano = TrabajoIntegracion::create([
        'sistema_externo_id' => $sistema->id,
        'tipo' => 'importar_grupo_pago_operaciones',
        'mecanismo' => 'playwright',
        'estado' => 'en_progreso',
        'iniciado_en' => now()->subMinutes(91),
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-grupo-pago-operaciones'));

    $nuevoTrabajo = TrabajoIntegracion::where('estado', 'en_progreso')->sole();
    $response->assertRedirect(route('sgf.importaciones.show', $nuevoTrabajo));
    expect($huerfano->refresh()->estado)->toBe('huerfano');
    Queue::assertPushed(ImportarCasosGrupoPagoOperacionesSgfJob::class, 1);
});

test('un usuario sin permiso no puede disparar la importación del grupo Pago Operaciones', function () {
    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(route('sgf.casos.importar-grupo-pago-operaciones'));

    $response->assertForbidden();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});

test('si el conector de SGF no está autorizado no se crea ningún trabajo_integracion', function () {
    Queue::fake();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.importar_casos_sgf');

    $this->actingAs($usuario)->post(route('sgf.casos.importar-grupo-pago-operaciones'));

    expect(TrabajoIntegracion::count())->toBe(0);
    Queue::assertNotPushed(ImportarCasosGrupoPagoOperacionesSgfJob::class);
});
