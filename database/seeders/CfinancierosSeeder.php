<?php

namespace Database\Seeders;

use App\Models\Jurisdiccion;
use Illuminate\Database\Seeder;

class CfinancierosSeeder extends Seeder
{
    /**
     * Seed the real centros financieros of jurisdicción 14 (Zonal Coyhaique).
     */
    public function run(): void
    {
        $jurisdiccion = Jurisdiccion::where('codigo', '14')->firstOrFail();

        $cfinancieros = [
            ['codigo' => '1400', 'nombre' => 'Administracion Zonal'],
            ['codigo' => '1401', 'nombre' => 'Garantía'],
            ['codigo' => '1402', 'nombre' => 'Oral'],
            ['codigo' => '1431', 'nombre' => 'Laboral'],
            ['codigo' => '1451', 'nombre' => 'Familia'],
            ['codigo' => '1471', 'nombre' => 'Competencia Común'],
        ];

        foreach ($cfinancieros as $cfinanciero) {
            $jurisdiccion->cfinancieros()->firstOrCreate(
                ['codigo' => $cfinanciero['codigo']],
                ['nombre' => $cfinanciero['nombre'], 'activo' => true],
            );
        }
    }
}
