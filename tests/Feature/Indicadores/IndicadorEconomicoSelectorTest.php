<?php

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Services\Indicadores\IndicadorEconomicoSelector;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

function crearIndicador(array $atributos): IndicadorEconomico
{
    $importacion = IndicadorEconomicoImportacion::create(['tipo_importacion' => 'diaria_usd', 'estado' => 'success']);

    return IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'nombre' => 'Indicador de prueba',
        'tipo' => 'moneda',
        'periodicidad_valor' => 'diaria',
        'unidad_medida' => 'CLP',
        'moneda_base' => 'CLP',
        'fuente' => 'CMF',
        ...$atributos,
    ]);
}

test('paraFecha selecciona UF por fecha_valor exacta', function () {
    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);

    $resultado = app(IndicadorEconomicoSelector::class)->paraFecha('UF', CarbonImmutable::parse('2026-06-10'));

    expect((float) $resultado->valor)->toBe(40765.97);
});

test('paraFecha aplica el fallback de último valor disponible para USD sin valor exacto', function () {
    crearIndicador(['codigo' => 'USD', 'fecha_valor' => '2026-06-12', 'valor' => 916.97]);

    $resultado = app(IndicadorEconomicoSelector::class)->paraFecha('USD', CarbonImmutable::parse('2026-06-14'));

    expect($resultado)->not->toBeNull();
    expect($resultado->fecha_valor->toDateString())->toBe('2026-06-12');
});

test('paraFecha retorna null para USD si no hay ningún valor anterior', function () {
    $resultado = app(IndicadorEconomicoSelector::class)->paraFecha('USD', CarbonImmutable::parse('2026-06-14'));

    expect($resultado)->toBeNull();
});

test('paraPeriodo selecciona UTM/UTA/IPC por periodo', function () {
    crearIndicador(['codigo' => 'UTM', 'periodo' => '2026-06', 'valor' => 71506, 'periodicidad_valor' => 'mensual']);
    crearIndicador(['codigo' => 'UTA', 'periodo' => '2025', 'valor' => 834504, 'periodicidad_valor' => 'anual']);

    $selector = app(IndicadorEconomicoSelector::class);

    expect((float) $selector->paraPeriodo('UTM', '2026-06')->valor)->toBe(71506.0);
    expect((float) $selector->paraPeriodo('UTA', '2025')->valor)->toBe(834504.0);
});

test('ultimosPorTipo sirve desde caché sin volver a consultar la base de datos', function () {
    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);

    $selector = app(IndicadorEconomicoSelector::class);

    $primero = $selector->ultimosPorTipo(['UF']);
    expect($primero)->toHaveCount(1);

    DB::enableQueryLog();
    $segundo = $selector->ultimosPorTipo(['UF']);
    $consultas = DB::getQueryLog();
    DB::disableQueryLog();

    expect($consultas)->toBeEmpty();
    expect($segundo)->toBe($primero);
});

test('ultimosPorTipo resuelve varios códigos sin caché vigente en una sola consulta', function () {
    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicador(['codigo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 916.97]);
    crearIndicador(['codigo' => 'UTM', 'periodo' => '2026-06', 'valor' => 71506, 'periodicidad_valor' => 'mensual']);

    $selector = app(IndicadorEconomicoSelector::class);

    DB::enableQueryLog();
    $resultado = $selector->ultimosPorTipo(['UF', 'USD', 'UTM']);
    $consultas = DB::getQueryLog();
    DB::disableQueryLog();

    expect($resultado)->toHaveCount(3);
    expect($consultas)->toHaveCount(1);
});

test('invalidarUltimoPorTipo hace que ultimosPorTipo refleje el nuevo valor sin esperar el TTL', function () {
    crearIndicador(['codigo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 900.0]);

    $selector = app(IndicadorEconomicoSelector::class);

    $primero = $selector->ultimosPorTipo(['USD']);
    expect($primero[0]['valor'])->toBe('900.0000');

    crearIndicador(['codigo' => 'USD', 'fecha_valor' => '2026-06-11', 'valor' => 910.0]);

    // Sin invalidar, la caché todavía vigente sigue devolviendo el valor viejo.
    $todaviaCacheado = $selector->ultimosPorTipo(['USD']);
    expect($todaviaCacheado[0]['valor'])->toBe('900.0000');

    $selector->invalidarUltimoPorTipo('USD');

    $actualizado = $selector->ultimosPorTipo(['USD']);
    expect($actualizado[0]['valor'])->toBe('910.0000');
});
