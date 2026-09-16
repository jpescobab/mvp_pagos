<?php

use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\Factura;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\TipoCompra;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use App\Services\PagoProveedores\DetalleFacturaService;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCcostoDePruebaParaSinEfectoWorkflow(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-SEW-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-SEW-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-SEW-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-SEW-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

function crearCasoConFacturaDePruebaSinEfectoWorkflow(string $sgfId): CasoPagoProveedor
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

    return app(CasoPagoProveedorImporter::class)->importarDesdeSnapshot($snapshot);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

test('registrar un DetalleFactura no dispara ninguna transición del Proceso del caso', function () {
    $caso = crearCasoConFacturaDePruebaSinEfectoWorkflow('sgf-sin-efecto-1');
    $factura = Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'proveedor_id' => $caso->proveedor_id,
        'folio' => 'F-sin-efecto-1',
        'monto' => 500000,
        'fecha_emision' => '2026-06-01',
    ]);

    $tipoCompra = TipoCompra::firstOrCreate(['codigo' => 'BIENES'], ['nombre' => 'Bienes']);
    $ccosto = crearCcostoDePruebaParaSinEfectoWorkflow();

    $estadoAntes = $caso->proceso->estado_actual_id;

    app(DetalleFacturaService::class)->registrar($factura, [
        'tipo_compra_id' => $tipoCompra->id,
        'ccosto_id' => $ccosto->id,
        'cantidad' => 10,
        'unidad_medida' => 'unidades',
        'monto' => 500000,
    ]);

    expect($caso->proceso->refresh()->estado_actual_id)->toBe($estadoAntes);
});

test('un caso con facturas sin detalle conserva sus transiciones normales disponibles', function () {
    $caso = crearCasoConFacturaDePruebaSinEfectoWorkflow('sgf-sin-efecto-2');
    Factura::create([
        'caso_pago_proveedor_id' => $caso->id,
        'proveedor_id' => $caso->proveedor_id,
        'folio' => 'F-sin-efecto-2',
        'monto' => 500000,
        'fecha_emision' => '2026-06-01',
    ]);

    $caso->proceso->load('definicionWorkflow.transiciones');

    expect($caso->proceso->definicionWorkflow->transiciones)->not->toBeEmpty();
});
