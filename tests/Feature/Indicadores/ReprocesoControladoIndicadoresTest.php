<?php

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use Illuminate\Support\Facades\Http;

test('indicadores:importar-mensual --periodo reprocesa un período puntual y omite lo ya existente', function () {
    Http::fake([
        '*/uf/2026/7*' => Http::response(['UFs' => [['Valor' => '40.000,00', 'Fecha' => '2026-07-10']]]),
        '*/uf/2026/8*' => Http::response(['UFs' => [['Valor' => '40.010,00', 'Fecha' => '2026-08-01']]]),
        '*/utm/2026*' => Http::response(['UTMs' => [['Valor' => '69.542', 'Fecha' => '2026-07-01']]]),
        '*/utm/2025*' => Http::response(['UTMs' => []]),
        '*/ipc*' => Http::response(['IPCs' => [['Valor' => '0,2', 'Fecha' => '2026-06-01']]]),
    ]);

    $this->artisan('indicadores:importar-mensual', ['--periodo' => '2026-07'])->assertExitCode(0);

    $primeraImportacion = IndicadorEconomicoImportacion::latest('id')->first();
    expect($primeraImportacion->tipo_importacion)->toBe('reproceso_controlado');
    expect($primeraImportacion->periodo)->toBe('2026-07');

    $creadosPrimeraVez = IndicadorEconomico::count();
    expect($creadosPrimeraVez)->toBeGreaterThan(0);

    $this->artisan('indicadores:importar-mensual', ['--periodo' => '2026-07'])->assertExitCode(0);

    $segundaImportacion = IndicadorEconomicoImportacion::latest('id')->first();
    expect($segundaImportacion->tipo_importacion)->toBe('reproceso_controlado');
    expect($segundaImportacion->total_creados)->toBe(0);
    expect($segundaImportacion->total_omitidos)->toBeGreaterThan(0);
    expect(IndicadorEconomico::count())->toBe($creadosPrimeraVez);
});

test('indicadores:importar-usd --fecha reprocesa una fecha puntual y omite lo ya existente', function () {
    Http::fake([
        '*/dolar*' => Http::response(['Dolares' => [['Valor' => '916,97', 'Fecha' => '2026-06-15']]]),
    ]);

    $this->artisan('indicadores:importar-usd', ['--fecha' => '2026-06-15'])->assertExitCode(0);

    $primeraImportacion = IndicadorEconomicoImportacion::latest('id')->first();
    expect($primeraImportacion->tipo_importacion)->toBe('reproceso_controlado');
    expect(IndicadorEconomico::where('codigo', 'USD')->count())->toBe(1);

    $this->artisan('indicadores:importar-usd', ['--fecha' => '2026-06-15'])->assertExitCode(0);

    $segundaImportacion = IndicadorEconomicoImportacion::latest('id')->first();
    expect($segundaImportacion->total_omitidos)->toBe(1);
    expect($segundaImportacion->total_creados)->toBe(0);
    expect(IndicadorEconomico::where('codigo', 'USD')->count())->toBe(1);
});
