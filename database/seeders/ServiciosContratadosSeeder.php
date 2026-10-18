<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ServiciosContratadosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'servicios_contratados.ver',
            'servicios_contratados.crear',
            'servicios_contratados.editar',
            'servicios_contratados.terminar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        Role::where('name', 'admin')->first()?->givePermissionTo($permisos);
        Role::where('name', 'administrativo_finanzas')->first()?->givePermissionTo('servicios_contratados.ver');
    }
}
