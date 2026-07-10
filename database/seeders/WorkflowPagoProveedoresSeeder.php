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
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        $admin = Role::where('name', 'admin')->first();
        $admin?->givePermissionTo($permisos);

        // Roles de las dos instancias de revisión de pagos (dominio pago de
        // proveedores). documentos.validar lo crea RolesAndPermissionsSeeder;
        // se garantiza aquí con firstOrCreate para ser independiente del orden.
        Permission::firstOrCreate(['name' => 'documentos.validar']);

        $jefeFinanzas = Role::firstOrCreate(['name' => 'jefe_finanzas']);
        $jefeFinanzas->givePermissionTo(['pago_proveedores.revisar_finanzas', 'documentos.validar']);

        $administradorZonal = Role::firstOrCreate(['name' => 'administrador_zonal']);
        $administradorZonal->givePermissionTo(['pago_proveedores.revisar_zonal', 'documentos.validar']);

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

        $transiciones = [
            ['codigo' => 'recibir_en_finanzas', 'nombre' => 'Recibir en Finanzas', 'de' => 'importada_desde_sgf', 'a' => 'recibida_finanzas'],
            ['codigo' => 'iniciar_revision_documental', 'nombre' => 'Iniciar revisión (Finanzas)', 'de' => 'recibida_finanzas', 'a' => 'en_revision_finanzas'],
            ['codigo' => 'observar_finanzas', 'nombre' => 'Observar (Finanzas)', 'de' => 'en_revision_finanzas', 'a' => 'observada', 'requiere_comentario' => true],
            ['codigo' => 'aprobar_finanzas', 'nombre' => 'Aprobar revisión de Finanzas', 'de' => 'en_revision_finanzas', 'a' => 'en_revision_zonal', 'documentos_requeridos' => ['FACTURA'], 'permiso_requerido' => 'pago_proveedores.revisar_finanzas'],
            ['codigo' => 'rechazar_finanzas', 'nombre' => 'Rechazar (Finanzas)', 'de' => 'en_revision_finanzas', 'a' => 'rechazada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.revisar_finanzas'],
            ['codigo' => 'devolver_a_finanzas', 'nombre' => 'Devolver a Finanzas', 'de' => 'en_revision_zonal', 'a' => 'en_revision_finanzas', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.revisar_zonal'],
            ['codigo' => 'aprobar_zonal', 'nombre' => 'Aprobar revisión Zonal', 'de' => 'en_revision_zonal', 'a' => 'lista_para_registro_cgu', 'documentos_requeridos' => ['FACTURA'], 'permiso_requerido' => 'pago_proveedores.revisar_zonal'],
            ['codigo' => 'rechazar_zonal', 'nombre' => 'Rechazar (Zonal)', 'de' => 'en_revision_zonal', 'a' => 'rechazada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.revisar_zonal'],
            ['codigo' => 'subsanar', 'nombre' => 'Subsanar', 'de' => 'observada', 'a' => 'subsanada'],
            ['codigo' => 'reenviar_revision', 'nombre' => 'Reenviar a revisión', 'de' => 'subsanada', 'a' => 'en_revision_finanzas'],
            ['codigo' => 'rechazar', 'nombre' => 'Rechazar', 'de' => 'observada', 'a' => 'rechazada', 'requiere_comentario' => true],
            ['codigo' => 'registrar_en_cgu', 'nombre' => 'Registrar en CGU', 'de' => 'lista_para_registro_cgu', 'a' => 'registrada_en_cgu', 'permiso_requerido' => 'pago_proveedores.registrar_cgu'],
            ['codigo' => 'marcar_lista_para_pago', 'nombre' => 'Marcar lista para pago', 'de' => 'registrada_en_cgu', 'a' => 'lista_para_pago'],
            ['codigo' => 'marcar_pagada_bancoestado', 'nombre' => 'Marcar pagada BancoEstado', 'de' => 'lista_para_pago', 'a' => 'pagada_bancoestado', 'permiso_requerido' => 'pago_proveedores.pagar'],
            ['codigo' => 'asociar_egreso_cgu', 'nombre' => 'Asociar egreso CGU', 'de' => 'pagada_bancoestado', 'a' => 'asociada_a_egreso_cgu'],
            ['codigo' => 'cerrar', 'nombre' => 'Cerrar', 'de' => 'asociada_a_egreso_cgu', 'a' => 'cerrada'],
            ['codigo' => 'anular', 'nombre' => 'Anular', 'de' => 'en_revision_finanzas', 'a' => 'anulada', 'requiere_comentario' => true, 'permiso_requerido' => 'pago_proveedores.anular'],
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
