<?php

namespace Database\Seeders;

use App\Models\SistemaExterno;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IntegracionesSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'integraciones.gestionar_conectores',
            'integraciones.ejecutar_playwright',
            'adquisiciones.consultar_orden_compra_mp',
            'adquisiciones.consultar_licitacion_mp',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        $sistemas = [
            ['codigo' => 'SGF', 'nombre' => 'SGF', 'activo' => true],
            ['codigo' => 'CGU', 'nombre' => 'CGU', 'activo' => false],
            ['codigo' => 'BANCOESTADO', 'nombre' => 'BancoEstado', 'activo' => false],
            ['codigo' => 'SII', 'nombre' => 'SII', 'activo' => false],
            ['codigo' => 'CMF', 'nombre' => 'CMF', 'activo' => false],
            ['codigo' => 'MERCADO_PUBLICO', 'nombre' => 'Mercado Público', 'activo' => true],
        ];

        foreach ($sistemas as $sistema) {
            SistemaExterno::firstOrCreate(
                ['codigo' => $sistema['codigo']],
                [
                    'nombre' => $sistema['nombre'],
                    'tipo_integracion' => 'manual',
                    'activo' => $sistema['activo'],
                ],
            );
        }
    }
}
