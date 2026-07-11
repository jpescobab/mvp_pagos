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
            'pago_proveedores.verificar_caso_sgf',
            'pago_proveedores.importar_casos_sgf',
            'pago_proveedores.revisar_finanzas',
            'pago_proveedores.revisar_zonal',
            'pago_proveedores.gestionar_caso',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        // Roles de las dos instancias de revisión de pagos (dominio pago de
        // proveedores). documentos.validar/documentos.gestionar los crea
        // RolesAndPermissionsSeeder; se garantizan aquí con firstOrCreate
        // para ser independientes del orden.
        Permission::firstOrCreate(['name' => 'documentos.validar']);
        Permission::firstOrCreate(['name' => 'documentos.gestionar']);

        $jefeFinanzas = Role::firstOrCreate(['name' => 'jefe_finanzas']);
        $jefeFinanzas->givePermissionTo([
            'pago_proveedores.revisar_finanzas',
            'pago_proveedores.gestionar_caso',
            'documentos.validar',
        ]);

        $administradorZonal = Role::firstOrCreate(['name' => 'administrador_zonal']);
        $administradorZonal->givePermissionTo(['pago_proveedores.revisar_zonal', 'documentos.validar']);

        // Rol operativo del tramo posterior a la revisión en dos instancias
        // (registrar CGU, marcar pagada BancoEstado, crear egresos, vincular
        // adquisiciones, registrar facturas, verificar SGF) — incluye
        // documentos.gestionar porque preparar el caso implica subir su
        // expediente, pero deliberadamente sin revisar_finanzas/revisar_zonal,
        // anular ni documentos.validar: quien prepara y registra el pago (y
        // sube sus documentos) no es quien lo aprueba, lo anula o valida sus
        // propios documentos.
        $administrativoFinanzas = Role::firstOrCreate(['name' => 'administrativo_finanzas']);
        $administrativoFinanzas->givePermissionTo([
            'pago_proveedores.registrar_egreso',
            'pago_proveedores.registrar_cgu',
            'pago_proveedores.pagar',
            'pago_proveedores.importar_casos_sgf',
            'pago_proveedores.vincular_adquisicion',
            'pago_proveedores.registrar_factura',
            'pago_proveedores.verificar_caso_sgf',
            'pago_proveedores.gestionar_caso',
            'documentos.gestionar',
        ]);

        $definicion = DefinicionWorkflow::firstOrCreate(
            ['codigo' => 'pago_proveedores'],
            ['nombre' => 'Pago de Proveedores', 'activo' => true],
        );

        $estados = [
            'importada_desde_sgf' => ['nombre' => 'Importada desde SGF', 'es_inicial' => true],
            'recibida_finanzas' => ['nombre' => 'Recibida en Finanzas'],
            'en_revision_finanzas' => ['nombre' => 'En revisión — Jefe de Finanzas'],
            'en_revision_zonal' => ['nombre' => 'En revisión — Administrador Zonal'],
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

        // 'pago_proveedores.gestionar_caso' cubre el trámite general del caso
        // (recepción, envío/reenvío a revisión, subsanación, rechazo desde
        // observada, y el tramo administrativo posterior al registro CGU) —
        // otorgado a jefe_finanzas y administrativo_finanzas. Las transiciones
        // propias de cada instancia de revisión (aprobar/rechazar/observar)
        // siguen exigiendo el permiso específico de esa instancia.
        $transiciones = [
            ['codigo' => 'recibir_en_finanzas', 'nombre' => 'Recibir en Finanzas', 'de' => 'importada_desde_sgf', 'a' => 'recibida_finanzas', 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'iniciar_revision_documental', 'nombre' => 'Iniciar revisión (Finanzas)', 'de' => 'recibida_finanzas', 'a' => 'en_revision_finanzas', 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'observar_finanzas', 'nombre' => 'Observar (Finanzas)', 'de' => 'en_revision_finanzas', 'a' => 'observada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.revisar_finanzas'],
            ['codigo' => 'aprobar_finanzas', 'nombre' => 'Aprobar revisión de Finanzas', 'de' => 'en_revision_finanzas', 'a' => 'en_revision_zonal', 'documentos_requeridos' => ['FACTURA'], 'permiso_requerido' => 'pago_proveedores.revisar_finanzas'],
            ['codigo' => 'rechazar_finanzas', 'nombre' => 'Rechazar (Finanzas)', 'de' => 'en_revision_finanzas', 'a' => 'rechazada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.revisar_finanzas'],
            ['codigo' => 'devolver_a_finanzas', 'nombre' => 'Devolver a Finanzas', 'de' => 'en_revision_zonal', 'a' => 'en_revision_finanzas', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.revisar_zonal'],
            ['codigo' => 'aprobar_zonal', 'nombre' => 'Aprobar revisión Zonal', 'de' => 'en_revision_zonal', 'a' => 'lista_para_registro_cgu', 'documentos_requeridos' => ['FACTURA'], 'permiso_requerido' => 'pago_proveedores.revisar_zonal'],
            ['codigo' => 'rechazar_zonal', 'nombre' => 'Rechazar (Zonal)', 'de' => 'en_revision_zonal', 'a' => 'rechazada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.revisar_zonal'],
            ['codigo' => 'subsanar', 'nombre' => 'Subsanar', 'de' => 'observada', 'a' => 'subsanada', 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'reenviar_revision', 'nombre' => 'Reenviar a revisión', 'de' => 'subsanada', 'a' => 'en_revision_finanzas', 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'rechazar', 'nombre' => 'Rechazar', 'de' => 'observada', 'a' => 'rechazada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'registrar_en_cgu', 'nombre' => 'Registrar en CGU', 'de' => 'lista_para_registro_cgu', 'a' => 'registrada_en_cgu', 'permiso_requerido' => 'pago_proveedores.registrar_cgu'],
            ['codigo' => 'marcar_lista_para_pago', 'nombre' => 'Marcar lista para pago', 'de' => 'registrada_en_cgu', 'a' => 'lista_para_pago', 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'marcar_pagada_bancoestado', 'nombre' => 'Marcar pagada BancoEstado', 'de' => 'lista_para_pago', 'a' => 'pagada_bancoestado', 'permiso_requerido' => 'pago_proveedores.pagar'],
            ['codigo' => 'asociar_egreso_cgu', 'nombre' => 'Asociar egreso CGU', 'de' => 'pagada_bancoestado', 'a' => 'asociada_a_egreso_cgu', 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'cerrar', 'nombre' => 'Cerrar', 'de' => 'asociada_a_egreso_cgu', 'a' => 'cerrada', 'permiso_requerido' => 'pago_proveedores.gestionar_caso'],
            ['codigo' => 'anular', 'nombre' => 'Anular', 'de' => 'en_revision_finanzas', 'a' => 'anulada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.anular'],
        ];

        foreach ($transiciones as $transicion) {
            $definicion->transiciones()->updateOrCreate(
                ['codigo' => $transicion['codigo']],
                [
                    'nombre' => $transicion['nombre'],
                    'estado_origen_id' => $estadosCreados[$transicion['de']]->id,
                    'estado_destino_id' => $estadosCreados[$transicion['a']]->id,
                    'requiere_comentario' => $transicion['requiere_comentario'] ?? false,
                    'permiso_requerido' => $transicion['permiso_requerido'],
                    'documentos_requeridos' => $transicion['documentos_requeridos'] ?? null,
                ],
            );
        }
    }
}
