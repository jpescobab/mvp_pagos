<?php

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Carbon\CarbonImmutable;

function crearIndicador(array $atributos): IndicadorEconomico
{
    $importacion = IndicadorEconomicoImportacion::create(['tipo' => 'diario', 'estado' => 'ok']);

    return IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'periodicidad_valor' => 'diaria',
        'fuente' => 'CMF',
        ...$atributos,
    ]);
}

test('paraFecha selecciona UF por fecha_valor exacta', function () {
    crearIndicador(['tipo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);

    $resultado = app(IndicadorEconomicoSelector::class)->paraFecha('UF', CarbonImmutable::parse('2026-06-10'));

    expect((float) $resultado->valor)->toBe(40765.97);
});

test('paraFecha aplica el fallback de último valor disponible para USD sin valor exacto', function () {
    crearIndicador(['tipo' => 'USD', 'fecha_valor' => '2026-06-12', 'valor' => 916.97]);

    $resultado = app(IndicadorEconomicoSelector::class)->paraFecha('USD', CarbonImmutable::parse('2026-06-14'));

    expect($resultado)->not->toBeNull();
    expect($resultado->fecha_valor->toDateString())->toBe('2026-06-12');
});

test('paraFecha retorna null para USD si no hay ningún valor anterior', function () {
    $resultado = app(IndicadorEconomicoSelector::class)->paraFecha('USD', CarbonImmutable::parse('2026-06-14'));

    expect($resultado)->toBeNull();
});

test('paraPeriodo selecciona UTM/UTA/IPC por periodo', function () {
    crearIndicador(['tipo' => 'UTM', 'periodo' => '2026-06', 'valor' => 71506, 'periodicidad_valor' => 'mensual']);
    crearIndicador(['tipo' => 'UTA', 'periodo' => '2025', 'valor' => 834504, 'periodicidad_valor' => 'anual']);

    $selector = app(IndicadorEconomicoSelector::class);

    expect((float) $selector->paraPeriodo('UTM', '2026-06')->valor)->toBe(71506.0);
    expect((float) $selector->paraPeriodo('UTA', '2025')->valor)->toBe(834504.0);
});
