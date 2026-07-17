<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListadoCasoPagoProveedorService
{
    /**
     * Estados avanzados/finales del workflow "pago_proveedores" que el
     * listado de casos oculta por defecto (sin que el usuario haya elegido
     * un filtro), porque ya no requieren revisión ni preparación de egreso.
     *
     * @var list<string>
     */
    private const ESTADOS_EXCLUIDOS_POR_DEFECTO = [
        'lista_para_registro_cgu',
        'registrada_en_cgu',
        'lista_para_pago',
        'pagada_bancoestado',
        'asociada_a_egreso_cgu',
        'cerrada',
        'rechazada',
        'anulada',
    ];

    public const FILTRO_TODOS = 'todos';

    /**
     * @return LengthAwarePaginator<int, CasoPagoProveedor>
     */
    public function paginar(?string $estadoFiltro, int $porPagina = 20): LengthAwarePaginator
    {
        return CasoPagoProveedor::with([
            'proveedor',
            'proceso.estadoActual',
            'proceso.definicionWorkflow.transiciones',
            'revisionesInstancia',
        ])
            ->when(
                $estadoFiltro === null,
                fn ($query) => $query->whereHas(
                    'proceso.estadoActual',
                    fn ($q) => $q->whereNotIn('codigo', self::ESTADOS_EXCLUIDOS_POR_DEFECTO),
                ),
            )
            ->when(
                $estadoFiltro !== null && $estadoFiltro !== self::FILTRO_TODOS,
                fn ($query) => $query->whereHas(
                    'proceso.estadoActual',
                    fn ($q) => $q->where('codigo', $estadoFiltro),
                ),
            )
            ->paginate($porPagina)
            ->withQueryString();
    }
}
