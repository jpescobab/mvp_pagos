<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use Database\Seeders\CoreInstitucionalSeeder;
use Illuminate\Database\QueryException;

test('el seeder crea la institución CAPJ activa y la jurisdicción inicial', function () {
    $this->seed(CoreInstitucionalSeeder::class);

    $capj = Institucion::where('codigo', 'CAPJ')->first();

    expect($capj)->not->toBeNull();
    expect($capj->activo)->toBeTrue();

    $jurisdiccion = $capj->jurisdicciones()->where('codigo', '14')->first();

    expect($jurisdiccion)->not->toBeNull();
    expect($jurisdiccion->nombre)->toBe('Zonal Coyhaique');
});

test('registrar un centro de costo guarda id interno, código único y traza hasta CAPJ', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $jurisdiccion = $institucion->jurisdicciones()->create(['codigo' => '14', 'nombre' => 'Zonal Coyhaique']);
    $cfinanciero = $jurisdiccion->cfinancieros()->create(['codigo' => 'CF-001', 'nombre' => 'Centro Financiero 1']);
    $ccosto = $cfinanciero->ccostos()->create(['codigo' => 'CC-001', 'nombre' => 'Centro de Costo 1']);

    expect($ccosto->id)->toBeInt();
    expect($ccosto->cfinanciero->jurisdiccion->institucion->is($institucion))->toBeTrue();

    expect(fn () => $cfinanciero->ccostos()->create(['codigo' => 'CC-001', 'nombre' => 'Duplicado']))
        ->toThrow(QueryException::class);
});

test('jurisdicciones.codigo usa "14" por defecto', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    $jurisdiccion = new Jurisdiccion(['nombre' => 'Zonal Coyhaique']);
    $jurisdiccion->institucion_id = $institucion->id;
    $jurisdiccion->save();

    expect($jurisdiccion->refresh()->codigo)->toBe('14');
});

test('el código institucional es único', function () {
    Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);

    expect(fn () => Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'Otra']))
        ->toThrow(QueryException::class);
});

test('no se puede borrar una institución con jurisdicciones asociadas', function () {
    $institucion = Institucion::create(['codigo' => 'CAPJ', 'nombre' => 'CAPJ']);
    $institucion->jurisdicciones()->create(['codigo' => '14', 'nombre' => 'Zonal Coyhaique']);

    expect(fn () => $institucion->delete())->toThrow(QueryException::class);
});
