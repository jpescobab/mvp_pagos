<?php

use App\Models\CasoPagoProveedor;
use App\Models\CorteReportabilidad;
use App\Models\PeriodoReportabilidad;
use App\Models\User;
use Database\Seeders\WorkflowInformesRazonadosSeeder;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @param  array<string, mixed>  $overrides
 */
function corteBorradorConPeriodo(string $codigo = '2026-06', array $overrides = []): CorteReportabilidad
{
    $periodo = PeriodoReportabilidad::create([
        'codigo' => $codigo,
        'fecha_inicio' => "{$codigo}-01",
        'fecha_fin' => "{$codigo}-28",
        'estado' => 'abierto',
    ]);

    return CorteReportabilidad::create(array_merge([
        'periodo_reportabilidad_id' => $periodo->id,
        'fecha_corte' => now(),
        'estado' => 'borrador',
    ], $overrides));
}

function usuarioQuePuedeGenerar(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('reportabilidad.generar_corte');

    return $usuario;
}

test('generar con permiso puebla el corte con items y snapshots de los casos del período', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteBorradorConPeriodo('2026-06');
    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'periodo' => '2026-06', 'monto' => 100]);
    CasoPagoProveedor::create(['sgf_id' => 'SGF-2', 'periodo' => '2026-06', 'monto' => 200]);
    CasoPagoProveedor::create(['sgf_id' => 'SGF-3', 'periodo' => '2026-07', 'monto' => 300]);

    $response = $this->actingAs(usuarioQuePuedeGenerar())
        ->post(route('reportabilidad.cortes.generar', $corte));

    $response->assertSessionHasNoErrors();
    expect($corte->items()->count())->toBe(2);
    expect($corte->snapshots()->count())->toBe(2);
    expect($corte->snapshots()->first()->hash)->not->toBeEmpty();
});

test('regenerar reemplaza el contenido previo sin duplicar', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteBorradorConPeriodo('2026-06');
    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'periodo' => '2026-06', 'monto' => 100]);
    $usuario = usuarioQuePuedeGenerar();

    $this->actingAs($usuario)->post(route('reportabilidad.cortes.generar', $corte))->assertSessionHasNoErrors();
    expect($corte->items()->count())->toBe(1);

    CasoPagoProveedor::create(['sgf_id' => 'SGF-2', 'periodo' => '2026-06', 'monto' => 200]);
    $this->actingAs($usuario)->post(route('reportabilidad.cortes.generar', $corte))->assertSessionHasNoErrors();

    expect($corte->items()->count())->toBe(2);
    expect($corte->snapshots()->count())->toBe(2);
});

test('generar sobre un corte publicado es bloqueado', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteBorradorConPeriodo('2026-06', ['estado' => 'publicado']);
    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'periodo' => '2026-06', 'monto' => 100]);

    $response = $this->actingAs(usuarioQuePuedeGenerar())
        ->post(route('reportabilidad.cortes.generar', $corte));

    $response->assertSessionHasErrors('corte');
    expect($corte->items()->count())->toBe(0);
});

test('generar sin el permiso reportabilidad.generar_corte responde 403', function () {
    $corte = corteBorradorConPeriodo('2026-06');
    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'periodo' => '2026-06', 'monto' => 100]);

    $response = $this->actingAs(User::factory()->create())
        ->post(route('reportabilidad.cortes.generar', $corte));

    $response->assertForbidden();
    expect($corte->items()->count())->toBe(0);
});

test('generar para un período sin casos deja el corte sin contenido y sin error', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteBorradorConPeriodo('2026-06');

    $response = $this->actingAs(usuarioQuePuedeGenerar())
        ->post(route('reportabilidad.cortes.generar', $corte));

    $response->assertSessionHasNoErrors();
    expect($corte->items()->count())->toBe(0);
});

test('el detalle del corte lista sus items con la entidad vinculada', function () {
    $this->seed(WorkflowInformesRazonadosSeeder::class);

    $corte = corteBorradorConPeriodo('2026-06');
    CasoPagoProveedor::create(['sgf_id' => 'SGF-1', 'periodo' => '2026-06', 'monto' => 100]);
    $usuario = usuarioQuePuedeGenerar();

    $this->actingAs($usuario)->post(route('reportabilidad.cortes.generar', $corte));

    $response = $this->actingAs($usuario)->get(route('reportabilidad.cortes.show', $corte));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('reportabilidad/cortes/show')
        ->where('corte.items_count', 1)
        ->has('corte.items', 1)
        ->where('corte.items.0.entidad_tipo', 'Caso Pago Proveedor')
    );
});
