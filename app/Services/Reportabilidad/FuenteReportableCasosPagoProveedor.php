<?php

namespace App\Services\Reportabilidad;

use App\Models\CasoPagoProveedor;
use App\Models\PeriodoReportabilidad;
use Illuminate\Database\Eloquent\Model;

/**
 * Fuente reportable de los casos de pago a proveedor de un período.
 *
 * Emparejamiento período↔caso: se comparan por igualdad exacta el `codigo` del
 * `periodo_reportabilidad` (formato `YYYY-MM`) contra el campo `periodo` del
 * caso. Ese `periodo` proviene tal cual de la importación SGF (texto sin
 * normalizar), por lo que el contrato asume que SGF entrega el período en el
 * mismo formato que el código del período de reportabilidad. Si datos reales
 * mostraran otro formato, este método `entidades()` es el único punto a ajustar
 * (p. ej. normalizando ambos lados) sin tocar el generador.
 */
class FuenteReportableCasosPagoProveedor implements FuenteReportable
{
    /**
     * @return iterable<int, Model>
     */
    public function entidades(PeriodoReportabilidad $periodo): iterable
    {
        return CasoPagoProveedor::query()
            ->where('periodo', $periodo->codigo)
            ->with('proveedor')
            ->get();
    }

    public function etiqueta(Model $entidad): string
    {
        /** @var CasoPagoProveedor $entidad */
        return "Caso de pago SGF {$entidad->sgf_id}";
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Model $entidad): array
    {
        /** @var CasoPagoProveedor $entidad */
        return [
            'tipo' => 'caso_pago_proveedor',
            'id' => $entidad->id,
            'sgf_id' => $entidad->sgf_id,
            'numero' => $entidad->numero,
            'periodo' => $entidad->periodo,
            'rut_proveedor' => $entidad->rut_proveedor,
            'proveedor' => $entidad->proveedor?->nombre,
            'monto' => $entidad->monto,
            'sgf_status' => $entidad->sgf_status,
            'folio_egreso' => $entidad->folio_egreso,
            'fecha_sii' => $entidad->fecha_sii,
        ];
    }
}
