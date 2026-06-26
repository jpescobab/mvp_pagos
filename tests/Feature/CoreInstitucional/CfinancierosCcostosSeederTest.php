<?php

use App\Models\Ccosto;
use App\Models\Cfinanciero;
use Database\Seeders\CcostosSeeder;
use Database\Seeders\CfinancierosSeeder;
use Database\Seeders\CoreInstitucionalSeeder;

beforeEach(function () {
    $this->seed(CoreInstitucionalSeeder::class);
    $this->seed(CfinancierosSeeder::class);
    $this->seed(CcostosSeeder::class);
});

test('el seeder crea los 6 centros financieros reales bajo la jurisdicción 14', function () {
    expect(Cfinanciero::count())->toBe(6);

    $codigos = Cfinanciero::pluck('codigo')->sort()->values()->all();
    expect($codigos)->toBe(['1400', '1401', '1402', '1431', '1451', '1471']);

    Cfinanciero::all()->each(
        fn (Cfinanciero $cfinanciero) => expect($cfinanciero->jurisdiccion->codigo)->toBe('14')
    );
});

test('el seeder crea los 31 centros de costo con su cfinanciero_id correcto', function () {
    expect(Ccosto::count())->toBe(31);

    $ccosto = Ccosto::where('codigo', '1400010201')->firstOrFail();
    expect($ccosto->cfinanciero->codigo)->toBe('1400');

    $ccosto = Ccosto::where('codigo', '1471031301')->firstOrFail();
    expect($ccosto->cfinanciero->codigo)->toBe('1471');
});

test('el nombre de 1471031301 quedó sembrado sin el error de codificación', function () {
    $ccosto = Ccosto::where('codigo', '1471031301')->firstOrFail();

    expect($ccosto->nombre)->toBe('JUZGADO DE LETRAS, GARANTÍA Y FAMILIA DE AISÉN');
    expect($ccosto->nombre)->not->toContain('Ã');
});
