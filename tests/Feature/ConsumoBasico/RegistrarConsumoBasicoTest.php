<?php

use App\Exceptions\ConsumoBasicoException;
use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\ConsumoBasico;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Models\SecurityAuditLog;
use App\Models\SistemaExterno;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use App\Services\ConsumoBasico\ConsumoBasicoService;
use App\Services\PagoProveedores\CasoPagoProveedorImporter;
use Database\Seeders\ConsumoBasicoSeeder;
use Database\Seeders\WorkflowPagoProveedoresSeeder;

function crearCcostoDePruebaParaConsumoBasico(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-CB-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-CB-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-CB-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-CB-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

function crearCasoPagoProveedorDePruebaParaConsumoBasico(Proveedor $proveedor, string $sgfId = 'sgf-consumo-1'): CasoPagoProveedor
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
function datosConsumoBasicoDePrueba(array $overrides = []): array
{
    return array_merge([
        'numero_documento' => 'FAE-1000',
        'numero_medidor' => 'MED-1000',
        'fecha_inicio_lectura' => '2026-06-01',
        'fecha_fin_lectura' => '2026-07-01',
        'fecha_emision' => '2026-07-05',
        'fecha_vencimiento' => '2026-07-20',
        'consumo' => 120.5,
        'tarifa' => 'BT1',
        'lectura_estimada' => false,
        'monto_neto' => 40000,
        'iva' => 7600,
        'monto_exento' => 0,
        'saldo_anterior' => 0,
        'monto_total' => 47600,
    ], $overrides);
}

beforeEach(function () {
    $this->seed(WorkflowPagoProveedoresSeeder::class);
    $this->seed(ConsumoBasicoSeeder::class);
});

test('registrar un ConsumoBasico vinculado a un caso sin detalle previo lo crea', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76500001-1', 'nombre' => 'EDELAYSEN']);
    $caso = crearCasoPagoProveedorDePruebaParaConsumoBasico($proveedor);
    $ccosto = crearCcostoDePruebaParaConsumoBasico();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '1234567',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $consumoBasico = app(ConsumoBasicoService::class)->registrar(
        $caso,
        [...datosConsumoBasicoDePrueba(), 'cliente_medidor_id' => $clienteMedidor->id],
    );

    expect($consumoBasico->cliente_medidor_id)->toBe($clienteMedidor->id);
    expect($consumoBasico->caso_pago_proveedor_id)->toBe($caso->id);
    expect(ConsumoBasico::count())->toBe(1);
});

test('registrar rechaza si faltan campos obligatorios vía el endpoint HTTP', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76500002-2', 'nombre' => 'EDELAYSEN']);
    $caso = crearCasoPagoProveedorDePruebaParaConsumoBasico($proveedor, 'sgf-consumo-2');
    $ccosto = crearCcostoDePruebaParaConsumoBasico();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '1234568',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $usuario = User::factory()->create();
    $usuario->givePermissionTo('consumo_basico.crear');

    $response = $this->actingAs($usuario)->post(
        route('consumo-basico.store', $caso),
        ['cliente_medidor_id' => $clienteMedidor->id],
    );

    $response->assertSessionHasErrors(['numero_documento', 'numero_medidor', 'monto_total']);
    expect(ConsumoBasico::count())->toBe(0);
});

test('registrar un segundo ConsumoBasico para un caso que ya tiene uno es rechazado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76500003-3', 'nombre' => 'EDELAYSEN']);
    $caso = crearCasoPagoProveedorDePruebaParaConsumoBasico($proveedor, 'sgf-consumo-3');
    $ccosto = crearCcostoDePruebaParaConsumoBasico();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '1234569',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $servicio = app(ConsumoBasicoService::class);
    $servicio->registrar($caso, [...datosConsumoBasicoDePrueba(), 'cliente_medidor_id' => $clienteMedidor->id]);

    expect(fn () => $servicio->registrar($caso, [...datosConsumoBasicoDePrueba(), 'cliente_medidor_id' => $clienteMedidor->id]))
        ->toThrow(ConsumoBasicoException::class);

    expect(ConsumoBasico::count())->toBe(1);
});

test('registrar un ConsumoBasico no dispara ninguna transición de workflow sobre el Proceso del caso', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76500004-4', 'nombre' => 'EDELAYSEN']);
    $caso = crearCasoPagoProveedorDePruebaParaConsumoBasico($proveedor, 'sgf-consumo-4');
    $ccosto = crearCcostoDePruebaParaConsumoBasico();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '1234570',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $estadoAntes = $caso->proceso->estado_actual_id;

    app(ConsumoBasicoService::class)->registrar($caso, [...datosConsumoBasicoDePrueba(), 'cliente_medidor_id' => $clienteMedidor->id]);

    expect($caso->proceso->refresh()->estado_actual_id)->toBe($estadoAntes);
});

test('un usuario sin permiso consumo_basico.crear no puede registrar y queda auditado', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76500005-5', 'nombre' => 'EDELAYSEN']);
    $caso = crearCasoPagoProveedorDePruebaParaConsumoBasico($proveedor, 'sgf-consumo-5');
    $ccosto = crearCcostoDePruebaParaConsumoBasico();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '1234571',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $usuario = User::factory()->create();

    $response = $this->actingAs($usuario)->post(
        route('consumo-basico.store', $caso),
        [...datosConsumoBasicoDePrueba(), 'cliente_medidor_id' => $clienteMedidor->id],
    );

    $response->assertForbidden();
    expect(ConsumoBasico::count())->toBe(0);
    expect(SecurityAuditLog::where('event', 'acceso_denegado')->exists())->toBeTrue();
});
