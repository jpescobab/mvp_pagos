<?php

use App\Models\Institucion;
use App\Models\Jurisdiccion;
use Database\Seeders\CoreInstitucionalSeeder;
use Database\Seeders\JurisdiccionesSeeder;

test('el seeder crea las 20 jurisdicciones nacionales', function () {
    $this->seed(CoreInstitucionalSeeder::class);
    $this->seed(JurisdiccionesSeeder::class);

    expect(Jurisdiccion::count())->toBe(20);
});

test('no sobrescribe el nombre de una jurisdicción ya sembrada', function () {
    $this->seed(CoreInstitucionalSeeder::class);

    $jurisdiccion14 = Jurisdiccion::where('codigo', '14')->first();
    expect($jurisdiccion14->nombre)->toBe('Zonal Coyhaique');

    $this->seed(JurisdiccionesSeeder::class);

    expect($jurisdiccion14->refresh()->nombre)->toBe('Zonal Coyhaique');
    expect(Jurisdiccion::where('codigo', '14')->count())->toBe(1);
});

test('la jurisdicción 00 queda con el nombre completo de CAPJ', function () {
    $this->seed(JurisdiccionesSeeder::class);

    $institucion = Institucion::where('codigo', 'CAPJ')->first();
    $jurisdiccion00 = $institucion->jurisdicciones()->where('codigo', '00')->first();

    expect($jurisdiccion00)->not->toBeNull();
    expect($jurisdiccion00->nombre)->toBe('Corporación Administrativa del Poder Judicial');
});
