<?php

namespace App\Http\Resources\Sgf;

use App\Models\CasoPagoProveedor;
use App\Models\SnapshotDatosExterno;
use App\Models\TrabajoIntegracion;
use App\Services\PagoProveedores\ListoParaEgresoResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin TrabajoIntegracion */
class ImportacionSgfResource extends JsonResource
{
    /**
     * @var Collection<string, CasoPagoProveedor>
     */
    private Collection $casosPorSgfId;

    public function __construct(TrabajoIntegracion $resource)
    {
        parent::__construct($resource);

        $this->casosPorSgfId = collect();
    }

    /**
     * @param  Collection<string, CasoPagoProveedor>  $casosPorSgfId  Mapa sgf_id => CasoPagoProveedor, para enlazar cada snapshot al caso que produjo sin N+1.
     */
    public function withCasos(Collection $casosPorSgfId): self
    {
        $this->casosPorSgfId = $casosPorSgfId;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $snapshotsCargados = $this->relationLoaded('snapshotsDatosExternos');
        $snapshots = $snapshotsCargados ? $this->mapSnapshots() : [];

        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'mecanismo' => $this->mecanismo,
            'iniciado_por' => $this->iniciadoPor?->name,
            'iniciado_en' => $this->iniciado_en,
            'finalizado_en' => $this->finalizado_en,
            'total_elementos' => $this->total_elementos,
            'estado' => $this->estado,
            'error' => $this->error,
            'desglose_estados' => $this->desgloseEstados,
            'eliminable' => $this->eliminable,
            'snapshots' => $this->when($snapshotsCargados, $snapshots),
            'resumen' => $this->when($snapshotsCargados, fn () => $this->resumen($snapshots)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapSnapshots(): array
    {
        return array_values($this->snapshotsDatosExternos
            ->map(function (SnapshotDatosExterno $snapshot) {
                $normalizado = $snapshot->payload_normalizado ?? [];
                $caso = $this->casosPorSgfId->get($snapshot->referencia_externa);

                return [
                    'id' => $snapshot->id,
                    'referencia_externa' => $snapshot->referencia_externa,
                    'hash' => $snapshot->hash,
                    'capturado_en' => $snapshot->capturado_en,
                    'proveedor' => $caso?->proveedor->nombre ?? ($normalizado['rut'] ?? null),
                    'rut' => $normalizado['rut'] ?? null,
                    'monto' => $normalizado['monto'] ?? null,
                    'estado_sgf' => $normalizado['estado'] ?? null,
                    'folio_egreso' => $normalizado['folio_egreso'] ?? null,
                    'numero' => $normalizado['numero'] ?? null,
                    'periodo' => $normalizado['periodo'] ?? null,
                    'fecha_sii' => $normalizado['fecha_sii'] ?? null,
                    'observacion' => $normalizado['observacion'] ?? null,
                    'caso_id' => $caso?->id,
                    'caso_estado' => $caso?->proceso?->estadoActual?->codigo,
                    'listo_para_egreso' => app(ListoParaEgresoResolver::class)->resuelve($caso),
                ];
            })
            ->all());
    }

    /**
     * @param  list<array<string, mixed>>  $snapshots
     * @return array<string, mixed>
     */
    private function resumen(array $snapshots): array
    {
        $montoTotal = 0.0;
        $identificados = 0;
        $noIdentificados = 0;
        $casosListos = 0;
        $casosPendientes = 0;

        foreach ($snapshots as $snapshot) {
            $montoTotal += (float) ($snapshot['monto'] ?? 0);

            $caso = $this->casosPorSgfId->get($snapshot['referencia_externa']);

            if ($caso?->proveedor_id !== null) {
                $identificados++;
            } else {
                $noIdentificados++;
            }

            if ($snapshot['listo_para_egreso']) {
                $casosListos++;
            } else {
                $casosPendientes++;
            }
        }

        return [
            'monto_total' => $montoTotal,
            'proveedores_identificados' => $identificados,
            'proveedores_no_identificados' => $noIdentificados,
            'casos_listos' => $casosListos,
            'casos_pendientes' => $casosPendientes,
        ];
    }
}
