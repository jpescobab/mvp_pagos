<?php

use App\Models\CasoPagoProveedor;
use App\Models\Ccosto;
use App\Models\ClienteMedidor;
use App\Models\Institucion;
use App\Models\Proveedor;
use App\Services\ConsumoBasico\ConsumoBasicoService;

function crearCcostoDePruebaParaCandidatoServicioBasico(): Ccosto
{
    $sufijo = fake()->unique()->numerify('####');

    $institucion = Institucion::create(['codigo' => "CAPJ-CSB-{$sufijo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-CSB-{$sufijo}", 'nombre' => 'Zonal']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => "CF-CSB-{$sufijo}", 'nombre' => 'Centro Financiero']);

    return $cfinanciero->ccostos()->create(['codigo' => "CC-CSB-{$sufijo}", 'nombre' => 'Centro de Costo']);
}

test('un caso cuyo proveedor tiene ClienteMedidor asociado se marca candidato', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76700001-1', 'nombre' => 'EDELAYSEN']);
    $ccosto = crearCcostoDePruebaParaCandidatoServicioBasico();
    ClienteMedidor::create([
        'numero_cliente' => '8888001',
        'proveedor_id' => $proveedor->id,
        'ccosto_id' => $ccosto->id,
        'tipo_suministro' => 'Electricidad',
        'activo' => true,
    ]);

    $caso = CasoPagoProveedor::create([
        'sgf_id' => 'sgf-candidato-1',
        'proveedor_id' => $proveedor->id,
        'rut_proveedor' => $proveedor->rutproveedor,
        'monto' => 10000,
    ]);

    expect(app(ConsumoBasicoService::class)->esCandidatoServicioBasico($caso))->toBeTrue();
});

test('un caso cuyo proveedor no tiene ningun ClienteMedidor asociado no se marca candidato', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '76700002-2', 'nombre' => 'Proveedor sin medidores']);

    $caso = CasoPagoProveedor::create([
        'sgf_id' => 'sgf-candidato-2',
        'proveedor_id' => $proveedor->id,
        'rut_proveedor' => $proveedor->rutproveedor,
        'monto' => 10000,
    ]);

    expect(app(ConsumoBasicoService::class)->esCandidatoServicioBasico($caso))->toBeFalse();
});

test('un caso sin proveedor resuelto no se marca candidato', function () {
    $caso = CasoPagoProveedor::create([
        'sgf_id' => 'sgf-candidato-3',
        'proveedor_id' => null,
        'monto' => 10000,
    ]);

    expect(app(ConsumoBasicoService::class)->esCandidatoServicioBasico($caso))->toBeFalse();
});
