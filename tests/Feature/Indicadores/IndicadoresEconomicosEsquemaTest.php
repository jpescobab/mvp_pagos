<?php

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use Illuminate\Database\QueryException;

test('el índice único rechaza un duplicado exacto de codigo+fecha_valor+periodo+fuente+es_proyectado', function () {
    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'diaria_usd', 'estado' => 'success']);

    $atributos = [
        'importacion_id' => $importacion->id,
        'codigo' => 'USD',
        'nombre' => 'Dólar observado',
        'tipo' => 'moneda',
        'fecha_valor' => '2026-06-15',
        'periodo' => null,
        'valor' => 916.97,
        'periodicidad_valor' => 'diaria',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'USD',
        'fuente' => 'CMF',
        'es_proyectado' => false,
    ];

    IndicadorEconomico::create($atributos);

    expect(fn () => IndicadorEconomico::create($atributos))
        ->toThrow(QueryException::class);

    expect(IndicadorEconomico::where('codigo', 'USD')->count())->toBe(1);
});

test('el mismo codigo con distinta fuente no colisiona con el índice único', function () {
    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'mensual_indicadores', 'estado' => 'success']);

    $base = [
        'importacion_id' => $importacion->id,
        'codigo' => 'UTA',
        'nombre' => 'Unidad Tributaria Anual',
        'tipo' => 'unidad_tributaria',
        'periodo' => '2025',
        'valor' => 834504,
        'periodicidad_valor' => 'anual',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'es_proyectado' => false,
    ];

    IndicadorEconomico::create([...$base, 'fuente' => 'calculado_utm']);
    IndicadorEconomico::create([...$base, 'fuente' => 'CMF']);

    expect(IndicadorEconomico::where('codigo', 'UTA')->count())->toBe(2);
});
