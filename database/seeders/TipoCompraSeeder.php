<?php

namespace Database\Seeders;

use App\Models\TipoCompra;
use Illuminate\Database\Seeder;

class TipoCompraSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'BIENES', 'nombre' => 'Bienes'],
            ['codigo' => 'SERVICIOS_GENERALES', 'nombre' => 'Servicios generales'],
            ['codigo' => 'OBRAS', 'nombre' => 'Obras'],
        ];

        foreach ($tipos as $tipo) {
            TipoCompra::firstOrCreate(['codigo' => $tipo['codigo']], ['nombre' => $tipo['nombre']]);
        }
    }
}
