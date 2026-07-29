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
            'reportabilidad.generar_corte',
            'informes.administrar',
            'informes.elaborar',
            'informes.aprobar',
            'informes.publicar',
            'informes.exportar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        // Roles operativos dedicados que materializan la separación de deberes
        // del flujo (elaborar ≠ aprobar/publicar ≠ preparar cortes). Bundlean
        // permisos ya existentes; `informes.administrar` queda fuera (config de
        // plantillas, propia de `admin`). Se usa syncPermissions para que el
        // conjunto sea exacto e idempotente; no se tocan `admin`/`superadmin`.
        $rolesDedicados = [
            'gestor_reportabilidad' => [
                'etiqueta' => 'Gestor de reportabilidad',
                'descripcion' => 'Prepara y publica los cortes de reportabilidad.',
                'permisos' => [
                    'reportabilidad.ver',
                    'reportabilidad.generar_corte',
                    'reportabilidad.publicar_corte',
                    'informes.ver',
                ],
            ],
            'elaborador_informes' => [
                'etiqueta' => 'Elaborador de informes',
                'descripcion' => 'Elabora el contenido de los informes razonados.',
                'permisos' => [
                    'informes.ver',
                    'informes.elaborar',
                    'informes.exportar',
                ],
            ],
            'revisor_informes' => [
                'etiqueta' => 'Revisor de informes',
                'descripcion' => 'Aprueba y publica los informes razonados.',
                'permisos' => [
                    'informes.ver',
                    'informes.aprobar',
                    'informes.publicar',
                    'informes.exportar',
                ],
            ],
        ];

        // `informes.ver` y `reportabilidad.ver` los crea RolesAndPermissionsSeeder;
        // se aseguran aquí con firstOrCreate para que este seeder sea
        // auto-suficiente aunque se ejecute aislado (varios tests lo siembran solo).
        foreach ($rolesDedicados as $config) {
            foreach ($config['permisos'] as $permiso) {
                Permission::firstOrCreate(['name' => $permiso]);
            }
        }

        foreach ($rolesDedicados as $nombreRol => $config) {
            $rol = Role::firstOrCreate(['name' => $nombreRol]);
            $rol->forceFill([
                'etiqueta' => $config['etiqueta'],
                'descripcion' => $config['descripcion'],
            ])->save();
            $rol->syncPermissions($config['permisos']);
        }

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
            ['codigo' => 'enviar_a_revision', 'nombre' => 'Enviar a revisión', 'de' => 'en_elaboracion', 'a' => 'en_revision', 'permiso_requerido' => 'informes.elaborar'],
            ['codigo' => 'aprobar', 'nombre' => 'Aprobar', 'de' => 'en_revision', 'a' => 'aprobado', 'permiso_requerido' => 'informes.aprobar'],
            ['codigo' => 'rechazar', 'nombre' => 'Rechazar', 'de' => 'en_revision', 'a' => 'rechazado', 'requiere_comentario' => true, 'permiso_requerido' => 'informes.aprobar'],
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
                    'permiso_requerido' => $transicion['permiso_requerido'],
                    'documentos_requeridos' => null,
                ],
            );
        }
    }
}
