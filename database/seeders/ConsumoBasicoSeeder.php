<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ConsumoBasicoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'consumo_basico.crear',
            'consumo_basico.editar',
            'consumo_basico.ver',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        // Consumo Básico completa el detalle de un caso de pago a
        // proveedores (servicios básicos) ya importado desde SGF — mismo
        // rol operativo que prepara y registra ese caso en
        // WorkflowPagoProveedoresSeeder.
        $administrativoFinanzas = Role::where('name', 'administrativo_finanzas')->first();
        $administrativoFinanzas?->givePermissionTo($permisos);
    }
}
