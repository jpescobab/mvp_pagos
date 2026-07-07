<?php

namespace Database\Seeders;

use App\Models\ConectorAutomatizacionNavegador;
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
            ['codigo' => 'SGF', 'nombre' => 'SGF', 'tipo_integracion' => 'playwright', 'activo' => true],
            ['codigo' => 'CGU', 'nombre' => 'CGU', 'tipo_integracion' => 'manual', 'activo' => false],
            ['codigo' => 'BANCOESTADO', 'nombre' => 'BancoEstado', 'tipo_integracion' => 'manual', 'activo' => false],
            ['codigo' => 'SII', 'nombre' => 'SII', 'tipo_integracion' => 'manual', 'activo' => false],
            ['codigo' => 'CMF', 'nombre' => 'CMF', 'tipo_integracion' => 'manual', 'activo' => false],
            ['codigo' => 'MERCADO_PUBLICO', 'nombre' => 'Mercado Público', 'tipo_integracion' => 'api', 'activo' => true],
        ];

        foreach ($sistemas as $sistema) {
            SistemaExterno::updateOrCreate(
                ['codigo' => $sistema['codigo']],
                [
                    'nombre' => $sistema['nombre'],
                    'tipo_integracion' => $sistema['tipo_integracion'],
                    'activo' => $sistema['activo'],
                ],
            );
        }

        $sgf = SistemaExterno::where('codigo', 'SGF')->firstOrFail();

        ConectorAutomatizacionNavegador::firstOrCreate(
            ['sistema_externo_id' => $sgf->id, 'codigo' => 'SGF_PLAYWRIGHT'],
            [
                'nombre' => 'Conector Playwright de SGF',
                'activo' => false,
                'descripcion' => 'Requiere autorización explícita de un usuario con integraciones.gestionar_conectores antes de su primer uso.',
            ],
        );
    }
}
