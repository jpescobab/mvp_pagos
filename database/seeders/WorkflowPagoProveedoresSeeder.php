<?php

namespace Database\Seeders;

use App\Models\DefinicionWorkflow;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkflowPagoProveedoresSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            'pago_proveedores.registrar_cgu',
            'pago_proveedores.pagar',
            'pago_proveedores.anular',
            'pago_proveedores.registrar_egreso',
            'pago_proveedores.vincular_adquisicion',
            'pago_proveedores.registrar_factura',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        $definicion = DefinicionWorkflow::firstOrCreate(
            ['codigo' => 'pago_proveedores'],
            ['nombre' => 'Pago de Proveedores', 'activo' => true],
        );

        $estados = [
            'importada_desde_sgf' => ['nombre' => 'Importada desde SGF', 'es_inicial' => true],
            'recibida_finanzas' => ['nombre' => 'Recibida en Finanzas'],
            'en_revision_documental' => ['nombre' => 'En revisión documental'],
            'observada' => ['nombre' => 'Observada'],
            'subsanada' => ['nombre' => 'Subsanada'],
            'lista_para_registro_cgu' => ['nombre' => 'Lista para registro CGU'],
            'registrada_en_cgu' => ['nombre' => 'Registrada en CGU'],
            'lista_para_pago' => ['nombre' => 'Lista para pago'],
            'pagada_bancoestado' => ['nombre' => 'Pagada BancoEstado'],
            'asociada_a_egreso_cgu' => ['nombre' => 'Asociada a egreso CGU'],
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
            ['codigo' => 'recibir_en_finanzas', 'nombre' => 'Recibir en Finanzas', 'de' => 'importada_desde_sgf', 'a' => 'recibida_finanzas'],
            ['codigo' => 'iniciar_revision_documental', 'nombre' => 'Iniciar revisión documental', 'de' => 'recibida_finanzas', 'a' => 'en_revision_documental'],
            ['codigo' => 'observar', 'nombre' => 'Observar', 'de' => 'en_revision_documental', 'a' => 'observada', 'requiere_comentario' => true],
            ['codigo' => 'aprobar_documentacion', 'nombre' => 'Aprobar documentación', 'de' => 'en_revision_documental', 'a' => 'lista_para_registro_cgu', 'documentos_requeridos' => ['FACTURA']],
            ['codigo' => 'subsanar', 'nombre' => 'Subsanar', 'de' => 'observada', 'a' => 'subsanada'],
            ['codigo' => 'reenviar_revision', 'nombre' => 'Reenviar a revisión', 'de' => 'subsanada', 'a' => 'en_revision_documental'],
            ['codigo' => 'rechazar', 'nombre' => 'Rechazar', 'de' => 'observada', 'a' => 'rechazada', 'requiere_comentario' => true],
            ['codigo' => 'registrar_en_cgu', 'nombre' => 'Registrar en CGU', 'de' => 'lista_para_registro_cgu', 'a' => 'registrada_en_cgu', 'permiso_requerido' => 'pago_proveedores.registrar_cgu'],
            ['codigo' => 'marcar_lista_para_pago', 'nombre' => 'Marcar lista para pago', 'de' => 'registrada_en_cgu', 'a' => 'lista_para_pago'],
            ['codigo' => 'marcar_pagada_bancoestado', 'nombre' => 'Marcar pagada BancoEstado', 'de' => 'lista_para_pago', 'a' => 'pagada_bancoestado', 'permiso_requerido' => 'pago_proveedores.pagar'],
            ['codigo' => 'asociar_egreso_cgu', 'nombre' => 'Asociar egreso CGU', 'de' => 'pagada_bancoestado', 'a' => 'asociada_a_egreso_cgu'],
            ['codigo' => 'cerrar', 'nombre' => 'Cerrar', 'de' => 'asociada_a_egreso_cgu', 'a' => 'cerrada'],
            ['codigo' => 'anular', 'nombre' => 'Anular', 'de' => 'en_revision_documental', 'a' => 'anulada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.anular'],
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
