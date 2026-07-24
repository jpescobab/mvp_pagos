<?php

use App\Models\CasoPagoProveedor;
use App\Models\DefinicionWorkflow;
use App\Models\EstadoWorkflow;
use App\Models\Proceso;
use App\Models\User;
use App\Notifications\TransicionWorkflowNotification;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Arma dos estados legibles bajo una definición y devuelve [anterior, nuevo].
 *
 * @return array{0: EstadoWorkflow, 1: EstadoWorkflow}
 */
function estadosLegibles(): array
{
    $definicion = DefinicionWorkflow::create(['codigo' => 'wf-notif-'.fake()->unique()->numberBetween(1, 999999), 'nombre' => 'WF Notif', 'activo' => true]);
    $anterior = $definicion->estados()->create(['codigo' => 'borrador', 'nombre' => 'Borrador', 'es_inicial' => true]);
    $nuevo = $definicion->estados()->create(['codigo' => 'revision', 'nombre' => 'En revisión']);

    return [$anterior, $nuevo];
}

function procesoConCaso(EstadoWorkflow $estadoActual): Proceso
{
    $caso = CasoPagoProveedor::create([
        'sgf_id' => (string) fake()->unique()->numberBetween(1000, 999999),
        'rut_proveedor' => '76123456-7',
        'monto' => 100000,
        'numero' => 'CP-'.fake()->unique()->numberBetween(1, 9999),
    ]);

    return Proceso::create([
        'definicion_workflow_id' => $estadoActual->definicion_workflow_id,
        'estado_actual_id' => $estadoActual->id,
        'sujeto_type' => CasoPagoProveedor::class,
        'sujeto_id' => $caso->id,
    ]);
}

function notificarTransicion(User $user): void
{
    [$anterior, $nuevo] = estadosLegibles();
    $proceso = procesoConCaso($anterior);

    $user->notify(new TransicionWorkflowNotification($proceso, $anterior, $nuevo));
}

test('el share expone el conteo de notificaciones no leídas del usuario', function () {
    $this->withoutVite();

    $user = User::factory()->create();
    notificarTransicion($user);
    notificarTransicion($user);

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(
        fn (Assert $page) => $page->where('notificaciones_no_leidas', 2),
    );
});

test('el conteo compartido es cero para quien no tiene notificaciones', function () {
    $this->withoutVite();

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertInertia(
        fn (Assert $page) => $page->where('notificaciones_no_leidas', 0),
    );
});

test('el conteo compartido cuenta solo las notificaciones del usuario', function () {
    $this->withoutVite();

    $propio = User::factory()->create();
    $otro = User::factory()->create();
    notificarTransicion($propio);
    notificarTransicion($otro);
    notificarTransicion($otro);

    $this->actingAs($propio)->get(route('dashboard'))->assertInertia(
        fn (Assert $page) => $page->where('notificaciones_no_leidas', 1),
    );
});

test('el endpoint lista solo las notificaciones propias, de la más reciente a la más antigua', function () {
    $propio = User::factory()->create();
    $otro = User::factory()->create();
    notificarTransicion($propio);
    notificarTransicion($propio);
    notificarTransicion($otro);

    $response = $this->actingAs($propio)->getJson(route('notificaciones.index'));

    $response->assertOk();
    // El proyecto usa JsonResource::withoutWrapping(): la colección responde un
    // array plano, sin envoltorio `data`.
    $datos = $response->json();

    expect($datos)->toHaveCount(2);
    expect($datos[0]['created_at'])->toBeGreaterThanOrEqual($datos[1]['created_at']);

    foreach ($datos as $notif) {
        expect($notif['descripcion'])->toContain('Caso de pago');
        expect($notif['estado_nuevo'])->toBe('En revisión');
        expect($notif['url'])->toContain('/pago-proveedores/casos/');
    }
});

test('marcar como leídas deja el conteo del usuario en cero sin tocar el de otro', function () {
    $propio = User::factory()->create();
    $otro = User::factory()->create();
    notificarTransicion($propio);
    notificarTransicion($otro);

    $this->actingAs($propio)->post(route('notificaciones.marcar-leidas'))->assertRedirect();

    expect($propio->fresh()->unreadNotifications()->count())->toBe(0);
    expect($otro->fresh()->unreadNotifications()->count())->toBe(1);
});

test('el payload de la transición es legible y navega al detalle del caso', function () {
    [$anterior, $nuevo] = estadosLegibles();
    $proceso = procesoConCaso($anterior);

    $notificacion = new TransicionWorkflowNotification($proceso, $anterior, $nuevo);
    $data = $notificacion->toDatabase(User::factory()->create());

    expect($data['estado_anterior_nombre'])->toBe('Borrador');
    expect($data['estado_nuevo_nombre'])->toBe('En revisión');
    expect($data['descripcion'])->toContain('Caso de pago');
    expect($data['url'])->toContain('/pago-proveedores/casos/'.$proceso->sujeto_id);
});

test('un proceso cuyo sujeto no tiene ruta de detalle deja la url en null', function () {
    $definicion = DefinicionWorkflow::create(['codigo' => 'wf-sin-ruta', 'nombre' => 'WF', 'activo' => true]);
    $estado = $definicion->estados()->create(['codigo' => 'x', 'nombre' => 'X', 'es_inicial' => true]);

    // Sujeto sin mapeo a una ruta de detalle (un User cualquiera como sujeto).
    $sujeto = User::factory()->create();
    $proceso = Proceso::create([
        'definicion_workflow_id' => $definicion->id,
        'estado_actual_id' => $estado->id,
        'sujeto_type' => User::class,
        'sujeto_id' => $sujeto->id,
    ]);

    $descriptor = $proceso->descriptorNotificacion();

    expect($descriptor['url'])->toBeNull();
    expect($descriptor['descripcion'])->toBe("Proceso #{$proceso->id}");
});
