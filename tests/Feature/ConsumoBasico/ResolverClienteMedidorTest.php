<?php

use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Services\ConsumoBasico\ConsumoBasicoService;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCcostoDePruebaParaResolverClienteMedidor(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-RCM-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-RCM-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-RCM-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-RCM-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

function crearCasoPagoProveedorDePruebaParaResolverClienteMedidor(Proveedor $proveedor, string $sgfId): CasoPagoProveedor
{
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
        'monto' => 45000.0,
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

/**
 * @return array<string, mixed>
 */
function datosConsumoBasicoDePruebaParaResolverClienteMedidor(): array
{
    return [
        'numero_documento' => 'FAE-2000',
        'numero_medidor' => 'MED-2000',
        'fecha_inicio_lectura' => '2026-06-01',
        'fecha_fin_lectura' => '2026-07-01',
        'fecha_emision' => '2026-07-05',
        'fecha_vencimiento' => '2026-07-20',
        'lectura_estimada' => false,
        'monto_neto' => 40000,
        'iva' => 7600,
        'monto_exento' => 0,
        'saldo_anterior' => 0,
        'monto_total' => 47600,
    ];
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
});

test('registrar con un numero_cliente ya existente vincula al ClienteMedidor sin duplicarlo', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76600001-1', 'nombre' => 'EDELAYSEN']);
    $caso = crearCasoPagoProveedorDePruebaParaResolverClienteMedidor($proveedor, 'sgf-resolver-1');
    $ccosto = crearCcostoDePruebaParaResolverClienteMedidor();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '9999001',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $consumoBasico = app(ConsumoBasicoService::class)->registrar(
        $caso,
        datosConsumoBasicoDePruebaParaResolverClienteMedidor(),
        ['numero_cliente' => '9999001'],
    );

    expect($consumoBasico->cliente_medidor_id)->toBe($clienteMedidor->id);
    expect(ClienteMedidor::count())->toBe(1);
});

test('registrar con un numero_cliente inexistente crea el ClienteMedidor al vuelo', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76600002-2', 'nombre' => 'EDELAYSEN']);
    $caso = crearCasoPagoProveedorDePruebaParaResolverClienteMedidor($proveedor, 'sgf-resolver-2');
    $ccosto = crearCcostoDePruebaParaResolverClienteMedidor();

    expect(ClienteMedidor::where('numero_cliente', '9999002')->exists())->toBeFalse();

    $consumoBasico = app(ConsumoBasicoService::class)->registrar(
        $caso,
        datosConsumoBasicoDePruebaParaResolverClienteMedidor(),
        [
            'numero_cliente' => '9999002',
            'ccosto_id' => $ccosto->id,
            'tipo_suministro' => 'Electricidad',
            'direccion_suministro' => 'Camino a Coyhaique Alto s/n',
        ],
    );

    $clienteMedidorCreado = ClienteMedidor::where('numero_cliente', '9999002')->first();

    expect($clienteMedidorCreado)->not->toBeNull();
    expect($clienteMedidorCreado->proveedor_id)->toBe($proveedor->id);
    expect($consumoBasico->cliente_medidor_id)->toBe($clienteMedidorCreado->id);
});
