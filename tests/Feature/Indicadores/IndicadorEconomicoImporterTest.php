<?php

use App\Models\IndicadorEconomico;
use App\Services\Indicadores\IndicadorEconomicoImporter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

function ufMesFake(int $anio, int $mes): array
{
    $dias = CarbonImmutable::create($anio, $mes, 1)->daysInMonth;

    return [
        'UFs' => collect(range(1, $dias))->map(fn (int $dia) => [
            'Valor' => '40.000,00',
            'Fecha' => CarbonImmutable::create($anio, $mes, $dia)->toDateString(),
        ])->all(),
    ];
}

function utmAnioFake(int $anio, bool $incluirDiciembre = true): array
{
    $meses = $incluirDiciembre ? 12 : 11;

    return [
        'UTMs' => collect(range(1, $meses))->map(fn (int $mes) => [
            'Valor' => '69.542',
            'Fecha' => CarbonImmutable::create($anio, $mes, 1)->toDateString(),
        ])->all(),
    ];
}

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 6, 15));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('importarMensual crea el tramo de UF cruzando dos meses calendario', function () {
    Http::fake([
        '*/uf/2026/6*' => Http::response(ufMesFake(2026, 6)),
        '*/uf/2026/7*' => Http::response(ufMesFake(2026, 7)),
        '*/utm/2026*' => Http::response(utmAnioFake(2026, incluirDiciembre: false)),
        '*/utm/2025*' => Http::response(utmAnioFake(2025)),
        '*/ipc*' => Http::response(['IPCs' => [['Valor' => '0,2', 'Fecha' => '2026-05-01']]]),
    ]);

    app(IndicadorEconomicoImporter::class)->importarMensual();

    $uf = IndicadorEconomico::where('tipo', 'UF')->orderBy('fecha_valor')->get();

    expect($uf)->toHaveCount(30); // 21 días de junio (10-30) + 9 días de julio (1-9)
    expect($uf->first()->fecha_valor->toDateString())->toBe('2026-06-10');
    expect($uf->last()->fecha_valor->toDateString())->toBe('2026-07-09');
    expect($uf->first()->vigente_desde->toDateString())->toBe('2026-06-10');
    expect($uf->first()->vigente_hasta->toDateString())->toBe('2026-07-09');
    expect($uf->first()->periodicidad_valor)->toBe('diaria');
    expect($uf->first()->periodicidad_publicacion)->toBe('tramo_mensual');
});

test('UTA se calcula desde la UTM de diciembre cuando está disponible', function () {
    Http::fake([
        '*/uf/2026/6*' => Http::response(ufMesFake(2026, 6)),
        '*/uf/2026/7*' => Http::response(ufMesFake(2026, 7)),
        '*/utm/2026*' => Http::response(utmAnioFake(2026, incluirDiciembre: false)),
        '*/utm/2025*' => Http::response(utmAnioFake(2025, incluirDiciembre: true)),
        '*/ipc*' => Http::response(['IPCs' => [['Valor' => '0,2', 'Fecha' => '2026-05-01']]]),
    ]);

    app(IndicadorEconomicoImporter::class)->importarMensual();

    $uta = IndicadorEconomico::where('tipo', 'UTA')->where('periodo', '2025')->first();

    expect($uta)->not->toBeNull();
    expect((float) $uta->valor)->toBe(69542.0 * 12);
    expect($uta->fuente)->toBe('calculado_utm');
});

test('UTA no se crea si la UTM de diciembre todavía no está disponible', function () {
    Http::fake([
        '*/uf/2026/6*' => Http::response(ufMesFake(2026, 6)),
        '*/uf/2026/7*' => Http::response(ufMesFake(2026, 7)),
        '*/utm/2026*' => Http::response(utmAnioFake(2026, incluirDiciembre: false)),
        '*/utm/2025*' => Http::response(utmAnioFake(2025, incluirDiciembre: false)),
        '*/ipc*' => Http::response(['IPCs' => [['Valor' => '0,2', 'Fecha' => '2026-05-01']]]),
    ]);

    app(IndicadorEconomicoImporter::class)->importarMensual();

    expect(IndicadorEconomico::where('tipo', 'UTA')->count())->toBe(0);
});

test('importarDolarDiario guarda el valor y advierte si la fecha no coincide con hoy', function () {
    Http::fake([
        '*/dolar*' => Http::response(['Dolares' => [['Valor' => '916,97', 'Fecha' => '2026-06-12']]]),
    ]);

    $importacion = app(IndicadorEconomicoImporter::class)->importarDolarDiario();

    expect($importacion->estado)->toBe('con_advertencias');
    expect($importacion->advertencias)->not->toBeEmpty();

    $usd = IndicadorEconomico::where('tipo', 'USD')->first();
    expect($usd->fecha_valor->toDateString())->toBe('2026-06-12');
    expect((float) $usd->valor)->toBe(916.97);
});

test('importarDolarDiario no advierte cuando la fecha coincide con hoy', function () {
    Http::fake([
        '*/dolar*' => Http::response(['Dolares' => [['Valor' => '916,97', 'Fecha' => '2026-06-15']]]),
    ]);

    $importacion = app(IndicadorEconomicoImporter::class)->importarDolarDiario();

    expect($importacion->estado)->toBe('ok');
});
