<?php

use App\Models\EgresoCgu;
use App\Models\User;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('el formulario de crear egreso excluye los casos que ya tienen un egreso asignado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $casoLibre = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-libre');
    $casoAsignado = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-asignado');

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-YA-ASIGNADO',
        'fecha' => now(),
        'monto_total' => $casoAsignado->monto,
    ]);
    $egreso->items()->create([
        'caso_pago_proveedor_id' => $casoAsignado->id,
        'monto' => $casoAsignado->monto,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.create'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/crear', shouldExist: false)
        ->where('casos', fn ($casos) => collect($casos)->pluck('id')->contains($casoLibre->id)
            && ! collect($casos)->pluck('id')->contains($casoAsignado->id))
    );
});

test('crear un egreso CGU con un caso que ya fue asignado a otro egreso es rechazado', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-carrera');

    $egresoExistente = EgresoCgu::create([
        'numero_egreso' => 'EGR-CARRERA-001',
        'fecha' => now(),
        'monto_total' => $caso->monto,
    ]);
    $egresoExistente->items()->create([
        'caso_pago_proveedor_id' => $caso->id,
        'monto' => $caso->monto,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-CARRERA-002',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto],
        ],
    ]);

    $response->assertSessionHasErrors('casos');
    expect(EgresoCgu::where('numero_egreso', 'EGR-CARRERA-002')->exists())->toBeFalse();
});

test('asignar un caso a un egreso lo avanza a en_revision_finanzas', function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $caso = crearCasoPagoProveedorDePrueba('sgf-crear-egreso-avanza');
    expect($caso->proceso->estadoActual->codigo)->toBe('importada_desde_sgf');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_egreso');

    $response = $this->actingAs($usuario)->post(route('pago-proveedores.egresos-cgu.store'), [
        'numero_egreso' => 'EGR-AVANZA-001',
        'fecha' => now()->toDateString(),
        'casos' => [
            ['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto],
        ],
    ]);

    $response->assertSessionHasNoErrors();
    expect($caso->proceso->refresh()->estadoActual->codigo)->toBe('en_revision_finanzas');
});
