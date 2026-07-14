<?php

namespace Database\Seeders;

use App\Models\TipoProcesoPago;
use Illuminate\Database\Seeder;

class TiposProcesoPagoSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['codigo' => 'COMPRA', 'nombre' => 'Compra'],
            ['codigo' => 'CONTRATO', 'nombre' => 'Contrato'],
            ['codigo' => 'CONVENIO', 'nombre' => 'Convenio'],
            ['codigo' => 'REEMBOLSO', 'nombre' => 'Reembolso'],
            ['codigo' => 'ANTICIPO', 'nombre' => 'Anticipo'],
            ['codigo' => 'OTRO', 'nombre' => 'Otro'],
        ];

        foreach ($tipos as $tipo) {
            TipoProcesoPago::firstOrCreate(
                ['codigo' => $tipo['codigo']],
                ['nombre' => $tipo['nombre'], 'activo' => true],
            );
        }
    }
}
