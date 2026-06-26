<?php

namespace Database\Seeders;

use App\Models\Cfinanciero;
use Illuminate\Database\Seeder;

class CcostosSeeder extends Seeder
{
    /**
     * Seed the real centros de costo of jurisdicción 14 (Zonal Coyhaique),
     * resolved to their centro financiero by codigo.
     */
    public function run(): void
    {
        $ccostos = [
            ['codigo' => '1400010201', 'nombre' => 'CAPJ ZONAL COYHAIQUE', 'cfinanciero' => '1400'],
            ['codigo' => '1400020301', 'nombre' => 'CORTE DE APELACIONES DE COYHAIQUE', 'cfinanciero' => '1400'],
            ['codigo' => '1400020401', 'nombre' => 'PRIMER JUZGADO DE LETRAS DE COYHAIQUE', 'cfinanciero' => '1400'],
            ['codigo' => '1400020601', 'nombre' => 'JUZGADO DE LETRAS, GARANTIA Y FAMILIA DE CHILE CHICO', 'cfinanciero' => '1400'],
            ['codigo' => '1400020602', 'nombre' => 'JUZGADO DE LETRAS, GARANTIA Y FAMILIA PTO. CISNES', 'cfinanciero' => '1400'],
            ['codigo' => '1400020603', 'nombre' => 'JUZGADO DE LETRAS, GARANTIA Y FAMILIA DE COCHRANE', 'cfinanciero' => '1400'],
            ['codigo' => '1401030801', 'nombre' => 'JUZGADO DE GARANTIA', 'cfinanciero' => '1401'],
            ['codigo' => '1402030901', 'nombre' => 'TRIBUNAL DE JUICIO ORAL EN LO PENAL', 'cfinanciero' => '1402'],
            ['codigo' => '1431031101', 'nombre' => 'JUZGADO DE LETRAS DEL TRABAJO DE COYHAIQUE', 'cfinanciero' => '1431'],
            ['codigo' => '1451031001', 'nombre' => 'JUZGADO DE FAMILIA COYHAIQUE', 'cfinanciero' => '1451'],
            ['codigo' => '1471031301', 'nombre' => 'JUZGADO DE LETRAS, GARANTÍA Y FAMILIA DE AISÉN', 'cfinanciero' => '1471'],
            ['codigo' => '1400090001', 'nombre' => 'VIVIENDA JUDICIAL - 01', 'cfinanciero' => '1400'],
            ['codigo' => '1400090002', 'nombre' => 'VIVIENDA JUDICIAL - 02', 'cfinanciero' => '1400'],
            ['codigo' => '1400090003', 'nombre' => 'VIVIENDA JUDICIAL - 03', 'cfinanciero' => '1400'],
            ['codigo' => '1400090004', 'nombre' => 'VIVIENDA JUDICIAL - 04', 'cfinanciero' => '1400'],
            ['codigo' => '1400090005', 'nombre' => 'VIVIENDA JUDICIAL - 05', 'cfinanciero' => '1400'],
            ['codigo' => '1400090006', 'nombre' => 'VIVIENDA JUDICIAL - 06', 'cfinanciero' => '1400'],
            ['codigo' => '1400090007', 'nombre' => 'VIVIENDA JUDICIAL - 07', 'cfinanciero' => '1400'],
            ['codigo' => '1400090008', 'nombre' => 'VIVIENDA JUDICIAL - 08', 'cfinanciero' => '1400'],
            ['codigo' => '1400090009', 'nombre' => 'VIVIENDA JUDICIAL - 09', 'cfinanciero' => '1400'],
            ['codigo' => '1400090010', 'nombre' => 'VIVIENDA JUDICIAL - 10', 'cfinanciero' => '1400'],
            ['codigo' => '1400090011', 'nombre' => 'VIVIENDA JUDICIAL - 11', 'cfinanciero' => '1400'],
            ['codigo' => '1400090012', 'nombre' => 'VIVIENDA JUDICIAL - 12', 'cfinanciero' => '1400'],
            ['codigo' => '1400090013', 'nombre' => 'VIVIENDA JUDICIAL - 13', 'cfinanciero' => '1400'],
            ['codigo' => '1400090014', 'nombre' => 'VIVIENDA JUDICIAL - 14', 'cfinanciero' => '1400'],
            ['codigo' => '1400090015', 'nombre' => 'VIVIENDA JUDICIAL - 15', 'cfinanciero' => '1400'],
            ['codigo' => '1400090016', 'nombre' => 'VIVIENDA JUDICIAL - 16', 'cfinanciero' => '1400'],
            ['codigo' => '1400090017', 'nombre' => 'VIVIENDA JUDICIAL - 17', 'cfinanciero' => '1400'],
            ['codigo' => '1400090018', 'nombre' => 'VIVIENDA JUDICIAL - 18', 'cfinanciero' => '1400'],
            ['codigo' => '1400090019', 'nombre' => 'VIVIENDA JUDICIAL - 19', 'cfinanciero' => '1400'],
            ['codigo' => '1400090020', 'nombre' => 'VIVIENDA JUDICIAL - 20', 'cfinanciero' => '1400'],
        ];

        foreach ($ccostos as $ccosto) {
            $cfinanciero = Cfinanciero::where('codigo', $ccosto['cfinanciero'])->firstOrFail();

            $cfinanciero->ccostos()->firstOrCreate(
                ['codigo' => $ccosto['codigo']],
                ['nombre' => $ccosto['nombre'], 'activo' => true],
            );
        }
    }
}
