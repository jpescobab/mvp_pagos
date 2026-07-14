<?php

use App\Models\Cfinanciero;
use App\Models\Institucion;
use App\Services\PagoProveedores\CfinancieroPorDefectoResolver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

function crearCfinancieroDePrueba(string $codigo, bool $activo = true): Cfinanciero
{
    $institucion = Institucion::create(['codigo' => "CAPJ-CFD-{$codigo}", 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => "14-CFD-{$codigo}", 'nombre' => "Zonal {$codigo}"]);

    return $jurisdiccion->cfinancieros()->create(['codigo' => $codigo, 'nombre' => "Centro {$codigo}", 'activo' => $activo]);
}

beforeEach(function () {
    Cache::flush();
});

test('resuelve el cfinanciero_id correspondiente al código configurado', function () {
    $cfinanciero = crearCfinancieroDePrueba('1400');
    config(['pago-proveedores.cfinanciero_default_codigo' => '1400']);

    $resuelto = (new CfinancieroPorDefectoResolver)->resolver();

    expect($resuelto)->toBe($cfinanciero->id);
});

test('código configurado inexistente retorna null y loguea un warning', function () {
    config(['pago-proveedores.cfinanciero_default_codigo' => 'NO-EXISTE']);
    Log::spy();

    $resuelto = (new CfinancieroPorDefectoResolver)->resolver();

    expect($resuelto)->toBeNull();
    Log::shouldHaveReceived('warning')
        ->once()
        ->with('pago_proveedores.cfinanciero_default_no_resuelto', ['codigo_configurado' => 'NO-EXISTE']);
});

test('código configurado de un cfinanciero inactivo retorna null', function () {
    crearCfinancieroDePrueba('1400', activo: false);
    config(['pago-proveedores.cfinanciero_default_codigo' => '1400']);

    expect((new CfinancieroPorDefectoResolver)->resolver())->toBeNull();
});

test('el resultado queda cacheado y no repite la consulta en la segunda llamada', function () {
    $cfinanciero = crearCfinancieroDePrueba('1400');
    config(['pago-proveedores.cfinanciero_default_codigo' => '1400']);

    $resolver = new CfinancieroPorDefectoResolver;
    expect($resolver->resolver())->toBe($cfinanciero->id);

    DB::enableQueryLog();
    $segundaLlamada = $resolver->resolver();
    $queriesEnSegundaLlamada = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($segundaLlamada)->toBe($cfinanciero->id);
    expect($queriesEnSegundaLlamada)->toBe(0);
});
