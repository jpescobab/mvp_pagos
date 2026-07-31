<?php

namespace Database\Seeders;

use App\Models\DefinicionWorkflow;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkflowPresupuestoCdpSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'presupuesto.crear_cdp',
            'presupuesto.firmar_cdp',
            'presupuesto.anular_cdp',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $jefeFinanzas = Role::where('name', 'jefe_finanzas')->first();
        $jefeFinanzas?->givePermissionTo($permisos);

        $definicion = DefinicionWorkflow::firstOrCreate(
            ['codigo' => 'presupuesto_cdp'],
            ['nombre' => 'Certificado de Disponibilidad Presupuestaria', 'activo' => true],
        );

        $estados = [
            'borrador' => ['nombre' => 'Borrador', 'es_inicial' => true],
            'firmado' => ['nombre' => 'Firmado', 'es_final' => true],
        ];

        $estadosCreados = [];
        foreach ($estados as $codigo => $datos) {
            $estadosCreados[$codigo] = $definicion->estados()->firstOrCreate(
                ['codigo' => $codigo],
                $datos,
            );
        }

        $transiciones = [
            [
                'codigo' => 'firmar',
                'nombre' => 'Firmar',
                'de' => 'borrador',
                'a' => 'firmado',
                'requiere_comentario' => false,
                'permiso_requerido' => 'presupuesto.firmar_cdp',
                'documentos_requeridos' => null,
            ],
        ];

        foreach ($transiciones as $transicion) {
            $definicion->transiciones()->firstOrCreate(
                ['codigo' => $transicion['codigo']],
                [
                    'nombre' => $transicion['nombre'],
                    'estado_origen_id' => $estadosCreados[$transicion['de']]->id,
                    'estado_destino_id' => $estadosCreados[$transicion['a']]->id,
                    'requiere_comentario' => $transicion['requiere_comentario'],
                    'permiso_requerido' => $transicion['permiso_requerido'],
                    'documentos_requeridos' => $transicion['documentos_requeridos'],
                ],
            );
        }
    }
}
