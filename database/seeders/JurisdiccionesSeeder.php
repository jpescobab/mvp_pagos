<?php

namespace Database\Seeders;

use App\Models\Institucion;
use Illuminate\Database\Seeder;

class JurisdiccionesSeeder extends Seeder
{
    /**
     * Seed the national list of CAPJ jurisdicciones.
     */
    public function run(): void
    {
        $capj = Institucion::firstOrCreate(
            ['codigo' => 'CAPJ'],
            ['nombre' => 'Corporación Administrativa del Poder Judicial', 'activo' => true],
        );

        $jurisdicciones = [
            ['codigo' => '00', 'nombre' => 'Corporación Administrativa del Poder Judicial'],
            ['codigo' => '01', 'nombre' => 'Arica'],
            ['codigo' => '02', 'nombre' => 'Iquique'],
            ['codigo' => '03', 'nombre' => 'Antofagasta'],
            ['codigo' => '04', 'nombre' => 'Copiapó'],
            ['codigo' => '05', 'nombre' => 'La Serena'],
            ['codigo' => '06', 'nombre' => 'Valparaíso'],
            ['codigo' => '07', 'nombre' => 'Rancagua'],
            ['codigo' => '08', 'nombre' => 'Talca'],
            ['codigo' => '09', 'nombre' => 'Chillán'],
            ['codigo' => '10', 'nombre' => 'Concepción'],
            ['codigo' => '11', 'nombre' => 'Temuco'],
            ['codigo' => '12', 'nombre' => 'Valdivia'],
            ['codigo' => '13', 'nombre' => 'Puerto Montt'],
            ['codigo' => '14', 'nombre' => 'Coyhaique'],
            ['codigo' => '15', 'nombre' => 'Punta Arenas'],
            ['codigo' => '16', 'nombre' => 'San Miguel'],
            ['codigo' => '17', 'nombre' => 'Santiago'],
            ['codigo' => '18', 'nombre' => 'Nivel Central'],
            ['codigo' => '99', 'nombre' => 'Sin Clasificar'],
        ];

        foreach ($jurisdicciones as $jurisdiccion) {
            $capj->jurisdicciones()->firstOrCreate(
                ['codigo' => $jurisdiccion['codigo']],
                ['nombre' => $jurisdiccion['nombre'], 'descripcion' => null, 'activo' => true],
            );
        }
    }
}
