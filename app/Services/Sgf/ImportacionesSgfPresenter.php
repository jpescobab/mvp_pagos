<?php

namespace App\Services\Sgf;

use App\Models\CasoPagoProveedor;
use App\Models\SnapshotDatosExterno;
use App\Models\TrabajoIntegracion;
use Illuminate\Support\Collection;

/**
 * Arma, para una página del listado de Importaciones SGF, el desglose de
 * etapas del workflow de los casos que cada corrida produjo y si es elegible
 * para eliminarse. Todo el cruce corrida→snapshot→caso→estado se resuelve en
 * dos consultas en bloque para no incurrir en N+1 por corrida.
 */
class ImportacionesSgfPresenter
{
    /**
     * @param  Collection<int, TrabajoIntegracion>  $trabajos
     * @return array{
     *     desglosePorTrabajo: array<int, list<array{estado_codigo: string, estado_nombre: string, cantidad: int}>>,
     *     eliminablePorTrabajo: array<int, bool>
     * }
     */
    public function contextoListado(Collection $trabajos): array
    {
        $trabajoIds = $trabajos->pluck('id')->all();

        /** @var Collection<int, SnapshotDatosExterno> $snapshots */
        $snapshots = $trabajoIds === []
            ? collect()
            : SnapshotDatosExterno::query()
                ->whereIn('trabajo_integracion_id', $trabajoIds)
                ->get(['id', 'trabajo_integracion_id', 'referencia_externa']);

        $trabajosConSnapshot = $snapshots->pluck('trabajo_integracion_id')->unique()->flip();

        $sgfIds = $snapshots->pluck('referencia_externa')->filter()->unique()->values()->all();

        /** @var Collection<string, CasoPagoProveedor> $casosPorSgfId */
        $casosPorSgfId = $sgfIds === []
            ? collect()
            : CasoPagoProveedor::query()
                ->whereIn('sgf_id', $sgfIds)
                ->with('proceso.estadoActual')
                ->get()
                ->keyBy('sgf_id');

        $snapshotsPorTrabajo = $snapshots->groupBy('trabajo_integracion_id');

        $desglosePorTrabajo = [];
        $eliminablePorTrabajo = [];

        foreach ($trabajos as $trabajo) {
            $eliminablePorTrabajo[$trabajo->id] = ! $trabajosConSnapshot->has($trabajo->id)
                && $trabajo->estado !== 'en_progreso';

            $desglosePorTrabajo[$trabajo->id] = $this->desglose(
                $snapshotsPorTrabajo->get($trabajo->id) ?? collect(),
                $casosPorSgfId,
            );
        }

        return [
            'desglosePorTrabajo' => $desglosePorTrabajo,
            'eliminablePorTrabajo' => $eliminablePorTrabajo,
        ];
    }

    /**
     * Cuenta los casos por estado del workflow, ordenados por el orden del
     * estado (su id, que sigue el orden de siembra del workflow). Los snapshots
     * sin caso/proceso no cuentan.
     *
     * @param  Collection<int, SnapshotDatosExterno>  $snapshots
     * @param  Collection<string, CasoPagoProveedor>  $casosPorSgfId
     * @return list<array{estado_codigo: string, estado_nombre: string, cantidad: int}>
     */
    private function desglose(Collection $snapshots, Collection $casosPorSgfId): array
    {
        /** @var array<int, array{orden: int, estado_codigo: string, estado_nombre: string, cantidad: int}> $conteo */
        $conteo = [];

        foreach ($snapshots->pluck('referencia_externa')->filter()->unique() as $sgfId) {
            $estado = $casosPorSgfId->get($sgfId)?->proceso?->estadoActual;

            if ($estado === null) {
                continue;
            }

            if (! isset($conteo[$estado->id])) {
                $conteo[$estado->id] = [
                    'orden' => $estado->id,
                    'estado_codigo' => $estado->codigo,
                    'estado_nombre' => $estado->nombre,
                    'cantidad' => 0,
                ];
            }

            $conteo[$estado->id]['cantidad']++;
        }

        usort($conteo, fn (array $a, array $b): int => $a['orden'] <=> $b['orden']);

        return array_map(
            fn (array $c): array => [
                'estado_codigo' => $c['estado_codigo'],
                'estado_nombre' => $c['estado_nombre'],
                'cantidad' => $c['cantidad'],
            ],
            $conteo,
        );
    }
}
