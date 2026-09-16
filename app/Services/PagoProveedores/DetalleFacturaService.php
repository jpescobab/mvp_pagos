<?php

namespace App\Services\PagoProveedores;

use App\Exceptions\DetalleFacturaException;
use App\Models\DetalleFactura;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;

class DetalleFacturaService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function registrar(Factura $factura, array $datos): DetalleFactura
    {
        // Consulta fresca (no la relación potencialmente ya cacheada en
        // $factura) — necesaria para detectar correctamente la unicidad 1:1
        // aunque se reciba la misma instancia dos veces en el mismo request.
        if ($factura->detalle()->exists()) {
            throw DetalleFacturaException::facturaYaTieneDetalle();
        }

        return DB::transaction(fn () => $factura->detalle()->create($datos));
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function actualizar(DetalleFactura $detalle, array $datos): DetalleFactura
    {
        $detalle->update($datos);

        return $detalle->refresh();
    }
}
