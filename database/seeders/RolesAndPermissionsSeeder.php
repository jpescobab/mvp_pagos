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
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.activar',
            'usuarios.desactivar',
            'usuarios.resetear_password',
            'usuarios.asignar_roles',
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

        Permission::where('name', 'usuarios.administrar')->delete();

        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.activar',
            'usuarios.desactivar',
            'usuarios.resetear_password',
            'usuarios.asignar_roles',
            'core_institucional.administrar',
            'tablas_maestras.administrar',
            'documentos.gestionar',
            'documentos.validar',
            'auditoria.ver',
        ]);
    }
}
