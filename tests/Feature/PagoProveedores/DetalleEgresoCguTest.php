<?php

use App\Models\EgresoCgu;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function crearEgresoCguDePrueba(): EgresoCgu
{
    $caso = crearCasoPagoProveedorDePrueba('sgf-egreso-'.fake()->unique()->numerify('####'));

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-'.fake()->unique()->numerify('####'),
        'fecha' => now(),
        'monto_total' => $caso->monto,
    ]);

    $egreso->items()->create([
        'caso_pago_proveedor_id' => $caso->id,
        'monto' => $caso->monto,
    ]);

    return $egreso;
}

test('el detalle de un egreso CGU muestra sus items', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $egreso = crearEgresoCguDePrueba();

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.show', $egreso));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/show')
        ->where('egreso.id', $egreso->id)
        ->has('egreso.items', 1)
    );
});
