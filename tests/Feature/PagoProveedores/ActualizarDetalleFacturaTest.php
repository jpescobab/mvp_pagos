<?php

use App\Models\Ccosto;
use App\Models\DetalleFactura;
use App\Models\Factura;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoCompra;
use App\Models\User;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\PagoProveedores\DetalleFacturaService;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCcostoDePruebaParaActualizarDetalleFactura(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-ADF-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-ADF-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-ADF-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-ADF-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

function crearDetalleFacturaDePrueba(): DetalleFactura
{
    $proveedor = Proveedor::create(['rutproveedor' => fake()->unique()->numerify('########-#'), 'nombre' => 'Proveedor de Prueba SpA']);

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => 'sgf-actualizar-detalle-1',
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

    $factura = Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'proveedor_id' => $caso->proveedor_id,
        'folio' => 'F-actualizar-1',
        'monto' => 500000,
        'fecha_emision' => '2026-06-01',
    ]);

    $tipoCompra = TipoCompra::firstOrCreate(['codigo' => 'BIENES'], ['nombre' => 'Bienes']);
    $ccosto = crearCcostoDePruebaParaActualizarDetalleFactura();

    return app(DetalleFacturaService::class)->registrar($factura, [
        'tipo_compra_id' => $tipoCompra->id,
        'ccosto_id' => $ccosto->id,
        'cantidad' => 10,
        'unidad_medida' => 'unidades',
        'monto' => 500000,
    ]);
}

function crearFacturaSinDetalleDePrueba(): Factura
{
    $proveedor = Proveedor::create(['rutproveedor' => fake()->unique()->numerify('########-#'), 'nombre' => 'Proveedor Sin Detalle SpA']);

    $sistema = SistemaExterno::firstOrCreate(
        ['codigo' => 'SGF'],
        ['nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
    );

    $normalizado = [
        'sgf_id' => 'sgf-sin-detalle-'.fake()->unique()->numerify('####'),
        'estado' => 'EN_TRAMITE',
        'grupo_actual' => 'FINANZAS',
        'observaciones' => null,
        'rut' => $proveedor->rutproveedor,
        'monto' => 300000.0,
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
        'folio' => 'F-sin-detalle',
        'monto' => 300000,
        'fecha_emision' => '2026-06-01',
    ]);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

test('editar el detalle de una factura sin detalle aún redirige a crear en vez de crashear', function () {
    $factura = crearFacturaSinDetalleDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_detalle_factura');

    $response = $this->actingAs($usuario)->get(
        route('pago-proveedores.facturas.detalle-factura.edit', $factura),
    );

    $response->assertRedirect(route('pago-proveedores.facturas.detalle-factura.create', $factura));
});

test('actualizar el detalle de una factura sin detalle aún devuelve un error de formulario, no un 404 crudo', function () {
    $factura = crearFacturaSinDetalleDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_detalle_factura');

    $response = $this->actingAs($usuario)->patch(
        route('pago-proveedores.facturas.detalle-factura.update', $factura),
        [
            'tipo_compra_id' => TipoCompra::firstOrCreate(['codigo' => 'BIENES'], ['nombre' => 'Bienes'])->id,
            'ccosto_id' => crearCcostoDePruebaParaActualizarDetalleFactura()->id,
            'cantidad' => 5,
            'monto' => 100000,
        ],
    );

    $response->assertSessionHasErrors('factura');
    expect(DetalleFactura::where('factura_id', $factura->id)->exists())->toBeFalse();
});

test('editar un DetalleFactura existente guarda los cambios', function () {
    $detalle = crearDetalleFacturaDePrueba();

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('pago_proveedores.registrar_detalle_factura');

    $response = $this->actingAs($usuario)->patch(
        route('pago-proveedores.facturas.detalle-factura.update', $detalle->factura),
        [
            'tipo_compra_id' => $detalle->tipo_compra_id,
            'ccosto_id' => $detalle->ccosto_id,
            'cantidad' => 25,
            'unidad_medida' => 'cajas',
            'monto' => 750000,
        ],
    );

    $response->assertSessionHasNoErrors();

    $detalle->refresh();
    expect((float) $detalle->cantidad)->toBe(25.0);
    expect($detalle->unidad_medida)->toBe('cajas');
    expect((float) $detalle->monto)->toBe(750000.0);
});
