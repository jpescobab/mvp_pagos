<?php

use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\ConsumoBasico;
use App\Models\Institucion;
use App\Models\Proveedor;

function crearCcostoDePruebaParaHistorialConsumo(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-HC-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-HC-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-HC-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-HC-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

/**
 * @return array<string, mixed>
 */
function datosConsumoBasicoParaHistorial(int $numeroCaso, string $fechaInicio): array
{
    return [
        'caso_pago_proveedor_id' => $numeroCaso,
        'numero_documento' => "FAE-{$numeroCaso}",
        'numero_medidor' => 'MED-4000',
        'fecha_inicio_lectura' => $fechaInicio,
        'fecha_fin_lectura' => date('Y-m-d', strtotime($fechaInicio.' +1 month')),
        'fecha_emision' => date('Y-m-d', strtotime($fechaInicio.' +35 days')),
        'fecha_vencimiento' => date('Y-m-d', strtotime($fechaInicio.' +50 days')),
        'lectura_estimada' => false,
        'monto_neto' => 40000,
        'iva' => 7600,
        'monto_exento' => 0,
        'saldo_anterior' => 0,
        'monto_total' => 47600,
    ];
}

test('el historial de un ClienteMedidor devuelve sus ConsumoBasico ordenados por período de lectura', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76900001-1', 'nombre' => 'EDELAYSEN']);
    $ccosto = crearCcostoDePruebaParaHistorialConsumo();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '6666001',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $casoMarzo = CasoPagoProveedor::create(['sgf_id' => 'sgf-historial-marzo', 'proveedor_id' => $proveedor->id, 'monto' => 10000]);
    $casoEnero = CasoPagoProveedor::create(['sgf_id' => 'sgf-historial-enero', 'proveedor_id' => $proveedor->id, 'monto' => 10000]);
    $casoFebrero = CasoPagoProveedor::create(['sgf_id' => 'sgf-historial-febrero', 'proveedor_id' => $proveedor->id, 'monto' => 10000]);

    $clienteMedidor->consumos()->create([
        ...datosConsumoBasicoParaHistorial($casoMarzo->id, '2026-03-01'),
    ]);
    $clienteMedidor->consumos()->create([
        ...datosConsumoBasicoParaHistorial($casoEnero->id, '2026-01-01'),
    ]);
    $clienteMedidor->consumos()->create([
        ...datosConsumoBasicoParaHistorial($casoFebrero->id, '2026-02-01'),
    ]);

    $periodos = $clienteMedidor->consumos()->get()->pluck('fecha_inicio_lectura')->map(fn ($fecha) => $fecha->toDateString())->all();

    expect($periodos)->toBe(['2026-01-01', '2026-02-01', '2026-03-01']);
});

test('un ClienteMedidor sin consumos registrados devuelve historial vacío sin error', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76900002-2', 'nombre' => 'EDELAYSEN']);
    $ccosto = crearCcostoDePruebaParaHistorialConsumo();
    $clienteMedidor = ClienteMedidor::create([
        'numero_cliente' => '6666002',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    expect($clienteMedidor->consumos()->get())->toHaveCount(0);
    expect(ConsumoBasico::where('cliente_medidor_id', $clienteMedidor->id)->count())->toBe(0);
});
