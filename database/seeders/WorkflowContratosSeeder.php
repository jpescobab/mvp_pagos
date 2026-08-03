<?php

namespace Database\Seeders;

use App\Models\DefinicionWorkflow;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkflowContratosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'contratos.ver',
            'contratos.crear',
            'contratos.editar',
            'contratos.aprobar',
            'contratos.rechazar',
            'contratos.vincular_pago',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        // Contratos es funcionalmente parte de Adquisiciones (ver design.md
        // del change modulo-contratos) — reutiliza el rol administrativo de
        // adquisiciones en vez de crear uno nuevo.
        $administrativoAdquisiciones = Role::where('name', 'administrativo_adquisiciones')->first();
        $administrativoAdquisiciones?->givePermissionTo([
            'contratos.ver',
            'contratos.crear',
            'contratos.editar',
        ]);

        $definicion = DefinicionWorkflow::firstOrCreate(
            ['codigo' => 'contratos'],
            ['nombre' => 'Contratos', 'activo' => true],
        );

        $estados = [
            'borrador' => ['nombre' => 'Borrador', 'es_inicial' => true],
            'pendiente' => ['nombre' => 'Pendiente'],
            'aprobado' => ['nombre' => 'Aprobado', 'es_final' => true],
            'rechazado' => ['nombre' => 'Rechazado', 'es_final' => true],
        ];

        $estadosCreados = [];
        foreach ($estados as $codigo => $datos) {
            $estadosCreados[$codigo] = $definicion->estados()->firstOrCreate(
                ['codigo' => $codigo],
                $datos,
            );
        }

        $transiciones = [
            ['codigo' => 'pendiente', 'nombre' => 'Enviar a revisión', 'de' => 'borrador', 'a' => 'pendiente'],
            ['codigo' => 'aprobar', 'nombre' => 'Aprobar', 'de' => 'pendiente', 'a' => 'aprobado', 'permiso_requerido' => 'contratos.aprobar', 'documentos_requeridos' => ['CONTRATO']],
            ['codigo' => 'rechazar', 'nombre' => 'Rechazar', 'de' => 'pendiente', 'a' => 'rechazado', 'requiere_comentario' => true, 'permiso_requerido' => 'contratos.rechazar'],
        ];

        foreach ($transiciones as $transicion) {
            $definicion->transiciones()->firstOrCreate(
                ['codigo' => $transicion['codigo']],
                [
                    'nombre' => $transicion['nombre'],
                    'estado_origen_id' => $estadosCreados[$transicion['de']]->id,
                    'estado_destino_id' => $estadosCreados[$transicion['a']]->id,
                    'requiere_comentario' => $transicion['requiere_comentario'] ?? false,
                    'permiso_requerido' => $transicion['permiso_requerido'] ?? null,
                    'documentos_requeridos' => $transicion['documentos_requeridos'] ?? null,
                ],
            );
        }
    }
}
