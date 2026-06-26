<?php

namespace Database\Seeders;

use App\Models\Institucion;
use Illuminate\Database\Seeder;

class CoreInstitucionalSeeder extends Seeder
{
    /**
     * Seed the institutional CAPJ hierarchy.
     */
    public function run(): void
    {
        $capj = Institucion::firstOrCreate(
            ['codigo' => 'CAPJ'],
            ['nombre' => 'Corporación Administrativa del Poder Judicial', 'activo' => true],
        );

        $capj->jurisdicciones()->firstOrCreate(
            ['codigo' => '14'],
            ['nombre' => 'Zonal Coyhaique', 'activo' => true],
        );
    }
}
