<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PresupuestoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'presupuesto.importar',
            'presupuesto.consultar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $jefeFinanzas = Role::where('name', 'jefe_finanzas')->first();
        $jefeFinanzas?->givePermissionTo($permisos);
    }
}
