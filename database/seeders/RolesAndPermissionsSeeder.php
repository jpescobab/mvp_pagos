<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the core roles and permissions. Module-specific permissions
     * (workflow, documentos, pago de proveedores, etc.) are added by their
     * own task when that module is built, following the `modulo.accion`
     * convention used here.
     */
    public function run(): void
    {
        $permissions = [
            'usuarios.administrar',
            'roles.administrar',
            'core_institucional.administrar',
            'tablas_maestras.administrar',
            'documentos.gestionar',
            'documentos.validar',
            'auditoria.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'usuarios.administrar',
            'core_institucional.administrar',
            'tablas_maestras.administrar',
            'documentos.gestionar',
            'documentos.validar',
            'auditoria.ver',
        ]);
    }
}
