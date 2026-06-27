<?php

namespace Database\Seeders;

use App\Models\DefinicionWorkflow;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkflowInformesRazonadosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'reportabilidad.publicar_corte',
            'informes.aprobar',
            'informes.publicar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        $definicion = DefinicionWorkflow::firstOrCreate(
            ['codigo' => 'informes_razonados'],
            ['nombre' => 'Informes Razonados', 'activo' => true],
        );

        $estados = [
            'en_elaboracion' => ['nombre' => 'En elaboración', 'es_inicial' => true],
            'en_revision' => ['nombre' => 'En revisión'],
            'aprobado' => ['nombre' => 'Aprobado'],
            'publicado' => ['nombre' => 'Publicado', 'es_final' => true],
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
            ['codigo' => 'enviar_a_revision', 'nombre' => 'Enviar a revisión', 'de' => 'en_elaboracion', 'a' => 'en_revision'],
            ['codigo' => 'aprobar', 'nombre' => 'Aprobar', 'de' => 'en_revision', 'a' => 'aprobado', 'permiso_requerido' => 'informes.aprobar'],
            ['codigo' => 'rechazar', 'nombre' => 'Rechazar', 'de' => 'en_revision', 'a' => 'rechazado', 'requiere_comentario' => true],
            ['codigo' => 'publicar', 'nombre' => 'Publicar', 'de' => 'aprobado', 'a' => 'publicado', 'permiso_requerido' => 'informes.publicar'],
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
                    'documentos_requeridos' => null,
                ],
            );
        }
    }
}
