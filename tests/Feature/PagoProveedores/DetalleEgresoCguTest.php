<?php

use App\Models\CasoPagoProveedor;
use App\Models\Cfinanciero;
use App\Models\EgresoCgu;
use App\Models\Institucion;
use App\Models\Jurisdiccion;
use App\Models\Proveedor;
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

test('el detalle expone proveedor, número de factura, estado del proceso y metadatos de cabecera', function () {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(WorkflowPagoProveedoresSeeder::class);

    $institucion = Institucion::create(['codigo' => 'INS-EGR', 'nombre' => 'Institución de prueba', 'activo' => true]);
    $jurisdiccion = Jurisdiccion::create(['institucion_id' => $institucion->id, 'codigo' => 'JUR-EGR', 'nombre' => 'Jurisdicción de prueba', 'activo' => true]);
    $cfinanciero = Cfinanciero::create(['jurisdiccion_id' => $jurisdiccion->id, 'codigo' => 'CF-EGR', 'nombre' => 'C. Apelaciones de Santiago', 'activo' => true]);

    // El importer solo enlaza un Proveedor existente por su RUT normalizado; el
    // helper crea el caso con RUT '11111111-1', así que lo creamos antes.
    Proveedor::create(['rutproveedor' => '11111111-1', 'nombre' => 'Sociedad Comercial Andes Ltda.', 'activo' => true]);

    $caso = crearCasoPagoProveedorDePrueba('sgf-egreso-rico-'.fake()->unique()->numerify('####'));
    $caso->update(['numero' => '293819', 'fecha_sii' => '2026-07-18']);

    $registrador = User::factory()->create(['name' => 'María Olivares']);

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-'.fake()->unique()->numerify('####'),
        'fecha' => now(),
        'monto_total' => $caso->monto,
        'periodo' => '2026-07',
        'cfinanciero_id' => $cfinanciero->id,
        'registrado_por' => $registrador->id,
    ]);
    $egreso->items()->create(['caso_pago_proveedor_id' => $caso->id, 'monto' => $caso->monto]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.show', $egreso));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/show')
        ->where('egreso.periodo', '2026-07')
        ->where('egreso.cfinanciero.nombre', 'C. Apelaciones de Santiago')
        ->where('egreso.registrado_por', 'María Olivares')
        ->where('egreso.cantidad_casos', 1)
        ->where('egreso.items.0.caso.id', $caso->id)
        ->where('egreso.items.0.numero', '293819')
        ->where('egreso.items.0.proveedor.nombre', 'Sociedad Comercial Andes Ltda.')
        ->where('egreso.items.0.proveedor.rutproveedor', '11111111-1')
        ->has('egreso.items.0.estado_actual.codigo')
    );
});

test('el detalle usa estado nulo cuando el caso no tiene proceso', function () {
    $this->withoutVite();

    $caso = CasoPagoProveedor::create([
        'sgf_id' => 'sgf-sin-proceso-'.fake()->unique()->numerify('####'),
        'monto' => 12345,
    ]);

    $egreso = EgresoCgu::create([
        'numero_egreso' => 'EGR-'.fake()->unique()->numerify('####'),
        'fecha' => now(),
        'monto_total' => 12345,
    ]);
    $egreso->items()->create(['caso_pago_proveedor_id' => $caso->id, 'monto' => 12345]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->get(route('pago-proveedores.egresos-cgu.show', $egreso));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('pago-proveedores/egresos-cgu/show')
        ->where('egreso.items.0.estado_actual', null)
        ->where('egreso.cantidad_casos', 1)
    );
});
