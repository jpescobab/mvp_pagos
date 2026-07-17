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

test('resolverUltimosPorTipo trae solo la fila más reciente por código cuando hay varias filas históricas', function () {
    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-08', 'valor' => 40700.0]);
    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-09', 'valor' => 40720.5]);

    crearIndicador(['codigo' => 'UTM', 'periodo' => '2026-05', 'valor' => 71000, 'periodicidad_valor' => 'mensual']);
    crearIndicador(['codigo' => 'UTM', 'periodo' => '2026-06', 'valor' => 71506, 'periodicidad_valor' => 'mensual']);

    $porCodigo = collect(app(IndicadorEconomicoSelector::class)->ultimosPorTipo(['UF', 'UTM']))->keyBy('codigo');

    expect($porCodigo['UF']['valor'])->toBe('40765.9700');
    expect($porCodigo['UF']['fecha_valor'])->toBe('2026-06-10');
    expect($porCodigo['UTM']['valor'])->toBe('71506.0000');
    expect($porCodigo['UTM']['periodo'])->toBe('2026-06');
});

// Los tests de abajo fuerzan CACHE_STORE=database porque el store `array`
// de test (phpunit.xml) no cuesta ninguna query — bajo ese store cualquier
// assertion de conteo de queries sobre Cache::get/many/put pasaría en verde
// sin probar nada del comportamiento real de producción, donde cada
// operación de caché es una query SQL real (DatabaseStore::get()/many()
// delegan en 1 sola query por invocación).

test('ultimosPorTipo bajo CACHE_STORE=database resuelve un código sin caché con exactamente 3 queries (caché miss, BD, caché put)', function () {
    config(['cache.default' => 'database']);

    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);

    $selector = app(IndicadorEconomicoSelector::class);

    DB::enableQueryLog();
    $resultado = $selector->ultimosPorTipo(['UF']);
    $consultas = DB::getQueryLog();
    DB::disableQueryLog();

    expect($resultado)->toHaveCount(1);
    expect($consultas)->toHaveCount(3);
});

test('ultimosPorTipo bajo CACHE_STORE=database no repite ninguna query para un código ya resuelto por una llamada anterior sobre la misma instancia', function () {
    config(['cache.default' => 'database']);

    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);

    $selector = app(IndicadorEconomicoSelector::class);
    $primero = $selector->ultimosPorTipo(['UF']);

    DB::enableQueryLog();
    $segundo = $selector->ultimosPorTipo(['UF']);
    $consultas = DB::getQueryLog();
    DB::disableQueryLog();

    expect($consultas)->toBeEmpty();
    expect($segundo)->toBe($primero);
});

test('ultimosPorTipo bajo CACHE_STORE=database, con códigos parcialmente solapados entre dos llamadas sobre la misma instancia, la segunda solo consulta el código nuevo', function () {
    config(['cache.default' => 'database']);

    crearIndicador(['codigo' => 'UF', 'fecha_valor' => '2026-06-10', 'valor' => 40765.97]);
    crearIndicador(['codigo' => 'USD', 'fecha_valor' => '2026-06-10', 'valor' => 916.97]);
    crearIndicador(['codigo' => 'UTM', 'periodo' => '2026-06', 'valor' => 71506, 'periodicidad_valor' => 'mensual']);

    $selector = app(IndicadorEconomicoSelector::class);
    $selector->ultimosPorTipo(['UF', 'USD']);

    DB::enableQueryLog();
    $resultado = $selector->ultimosPorTipo(['UF', 'USD', 'UTM']);
    $consultas = DB::getQueryLog();
    DB::disableQueryLog();

    expect($resultado)->toHaveCount(3);
    expect($consultas)->toHaveCount(3);
});

test('ultimosPorTipo bajo CACHE_STORE=database memoiza un código sin ningún indicador registrado y no lo vuelve a consultar en la misma instancia', function () {
    config(['cache.default' => 'database']);

    $selector = app(IndicadorEconomicoSelector::class);

    $primero = $selector->ultimosPorTipo(['CODIGO_INEXISTENTE']);
    expect($primero)->toBeEmpty();

    DB::enableQueryLog();
    $segundo = $selector->ultimosPorTipo(['CODIGO_INEXISTENTE']);
    $consultas = DB::getQueryLog();
    DB::disableQueryLog();

    expect($segundo)->toBeEmpty();
    expect($consultas)->toBeEmpty();
});
