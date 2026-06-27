<?php

namespace Database\Seeders;

use App\Models\ModalidadAdquisicion;
use Illuminate\Database\Seeder;

class ModalidadesAdquisicionSeeder extends Seeder
{
    public function run(): void
    {
        $modalidades = [
            ['codigo' => 'LICITACION_PUBLICA', 'nombre' => 'Licitación Pública'],
            ['codigo' => 'LICITACION_PRIVADA', 'nombre' => 'Licitación Privada'],
            ['codigo' => 'TRATO_DIRECTO', 'nombre' => 'Trato Directo'],
            ['codigo' => 'CONVENIO_MARCO', 'nombre' => 'Convenio Marco'],
        ];

        foreach ($modalidades as $modalidad) {
            ModalidadAdquisicion::firstOrCreate(
                ['codigo' => $modalidad['codigo']],
                ['nombre' => $modalidad['nombre'], 'activo' => true],
            );
        }
    }
}
