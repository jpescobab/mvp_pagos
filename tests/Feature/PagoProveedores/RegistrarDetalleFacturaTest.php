<?php

use App\Models\Ccosto;
use App\Models\DetalleFactura;
use App\Models\Factura;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoCompra;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCcostoDePruebaParaDetalleFactura(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-DF-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-DF-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-DF-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-DF-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

function crearFacturaDePruebaParaDetalleFactura(string $sgfId = 'sgf-detalle-factura-1'): Factura
{
    $proveedor = Proveedor::create(['rutproveedor' => fake()->unique()->numerify('########-#'), 'nombre' => 'Proveedor de Prueba SpA']);

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => $sgfId,
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => $proveedor->rutproveedor,
        'monto' => 500000.0,
    ];

    $snapshot = SnapshotDatosExterno::create([
        'sistema_externo_id' => $sistema->id,
        'metodo_captura' => 'playwright',
        'referencia_externa' => $normalizado['sgf_id'],
        'payload_crudo' => $normalizado,
        'payload_normalizado' => $normalizado,
        'hash' => hash('sha256', json_encode($normalizado, JSON_THROW_ON_ERROR)),
        'capturado_en' => now(),
    ]);

    $caso = app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);

    return Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'proveedor_id' => $caso->proveedor_id,
        'folio' => 'F-'.$sgfId,
        'monto' => 500000,
        'fecha_emision' => '2026-06-01',
    ]);
}

/**
 * @return array<string, mixed>
 */
function datosDetalleFacturaDePrueba(array $overrides = []): array
{
    $tipoCompra = TipoCompra::firstOrCreate(['codigo' => 'BIENES'], ['nombre' => 'Bienes']);
    $ccosto = crearCcostoDePruebaParaDetalleFactura();

    return array_merge([
        'tipo_compra_id' => $tipoCompra->id,
        'ccosto_id' => $ccosto->id,
        'cantidad' => 10,
        'unidad_medida' => 'unidades',
        'monto' => 500000,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

test('registrar el detalle de una factura sin detalle previo lo crea', function () {
    $factura = crearFacturaDePruebaParaDetalleFactura();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_detalle_factura');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.facturas.detalle-factura.store', $factura),
        datosDetalleFacturaDePrueba(),
    );

    $response->assertSessionHasNoErrors();

    $detalle = DetalleFactura::where('factura_id', $factura->id)->first();
    expect($detalle)->not->toBeNull();
    expect((float) $detalle->cantidad)->toBe(10.0);
});

test('registrar rechaza si faltan campos obligatorios', function () {
    $factura = crearFacturaDePruebaParaDetalleFactura('sgf-detalle-factura-2');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_detalle_factura');

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.facturas.detalle-factura.store', $factura),
        [],
    );

    $response->assertSessionHasErrors(['tipo_compra_id', 'ccosto_id', 'cantidad', 'monto']);
    expect(DetalleFactura::where('factura_id', $factura->id)->exists())->toBeFalse();
});

test('registrar un segundo detalle para una factura que ya tiene uno es rechazado', function () {
    $factura = crearFacturaDePruebaParaDetalleFactura('sgf-detalle-factura-3');

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_detalle_factura');

    $this->actingAs($usuario)->post(
        route('pago-proveedores.facturas.detalle-factura.store', $factura),
        datosDetalleFacturaDePrueba(),
    );

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.facturas.detalle-factura.store', $factura),
        datosDetalleFacturaDePrueba(),
    );

    $response->assertSessionHasErrors('factura');
    expect(DetalleFactura::where('factura_id', $factura->id)->count())->toBe(1);
});

test('un usuario sin permiso no puede registrar el detalle y queda auditado', function () {
    $factura = crearFacturaDePruebaParaDetalleFactura('sgf-detalle-factura-4');

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('pago-proveedores.facturas.detalle-factura.store', $factura),
        datosDetalleFacturaDePrueba(),
    );

    $response->assertForbidden();
    expect(DetalleFactura::where('factura_id', $factura->id)->exists())->toBeFalse();
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});
