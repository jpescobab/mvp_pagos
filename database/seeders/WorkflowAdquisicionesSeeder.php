<?php

namespace Database\Seeders;

use App\Models\DefinicionWorkflow;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkflowAdquisicionesSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'adquisiciones.publicar',
            'adquisiciones.adjudicar',
            'adquisiciones.anular',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        $definicion = DefinicionWorkflow::firstOrCreate(
            ['codigo' => 'adquisiciones'],
            ['nombre' => 'Adquisiciones', 'activo' => true],
        );

        $estados = [
            'borrador' => ['nombre' => 'Borrador', 'es_inicial' => true],
            'en_revision' => ['nombre' => 'En revisión'],
            'publicada' => ['nombre' => 'Publicada'],
            'adjudicada' => ['nombre' => 'Adjudicada'],
            'contratada' => ['nombre' => 'Contratada'],
            'cerrada' => ['nombre' => 'Cerrada', 'es_final' => true],
            'rechazada' => ['nombre' => 'Rechazada', 'es_final' => true],
            'anulada' => ['nombre' => 'Anulada', 'es_final' => true],
        ];

        $estadosCreados = [];
        foreach ($estados as $codigo => $datos) {
            $estadosCreados[$codigo] = $definicion->estados()->firstOrCreate(
                ['codigo' => $codigo],
                $datos,
            );
        }

        $transiciones = [
            ['codigo' => 'enviar_a_revision', 'nombre' => 'Enviar a revisión', 'de' => 'borrador', 'a' => 'en_revision'],
            ['codigo' => 'devolver_a_borrador', 'nombre' => 'Devolver a borrador', 'de' => 'en_revision', 'a' => 'borrador', 'requiere_comentario' => true],
            ['codigo' => 'publicar', 'nombre' => 'Publicar', 'de' => 'en_revision', 'a' => 'publicada', 'permiso_requerido' => 'adquisiciones.publicar'],
            ['codigo' => 'adjudicar', 'nombre' => 'Adjudicar', 'de' => 'publicada', 'a' => 'adjudicada', 'permiso_requerido' => 'adquisiciones.adjudicar'],
            ['codigo' => 'formalizar_contrato', 'nombre' => 'Formalizar contrato', 'de' => 'adjudicada', 'a' => 'contratada', 'documentos_requeridos' => ['CONTRATO']],
            ['codigo' => 'cerrar', 'nombre' => 'Cerrar', 'de' => 'contratada', 'a' => 'cerrada'],
            ['codigo' => 'rechazar', 'nombre' => 'Rechazar', 'de' => 'en_revision', 'a' => 'rechazada', 'requiere_comentario' => true],
            ['codigo' => 'anular', 'nombre' => 'Anular', 'de' => 'en_revision', 'a' => 'anulada', 'requiere_comentario' => true, 'permiso_requerido' => 'adquisiciones.anular'],
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
