<?php

namespace App\Services\Presupuesto;

use App\Exceptions\CertificadoDisponibilidadPresupuestariaException;
use App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria;
use Illuminate\Support\Facades\DB;

class AnularCertificadoDisponibilidadService
{
    public function __construct(
        private readonly CrearBorradorCertificadoDisponibilidadService $crearBorrador,
        private readonly FirmarCertificadoDisponibilidadService $firmar,
    ) {}

    public function anular(CertificadoDisponibilidadPresupuestaria $cdpOriginal, ?string $comentario = null): CertificadoDisponibilidadPresupuestaria
    {
        $estadoActual = $cdpOriginal->proceso->estadoActual->codigo;

        if ($estadoActual !== 'firmado') {
            throw CertificadoDisponibilidadPresupuestariaException::noFirmadoParaAnular($estadoActual);
        }

        return DB::transaction(function () use ($cdpOriginal, $comentario) {
            $anulacion = $this->crearBorrador->crear([
                'presupuesto_id' => $cdpOriginal->presupuesto_id,
                'cfinanciero_id' => $cdpOriginal->cfinanciero_id,
                'tipo_gasto' => $cdpOriginal->tipo_gasto,
                'codigo_iniciativa' => $cdpOriginal->codigo_iniciativa,
                'nombre' => "ANULA {$cdpOriginal->nombre}",
                'nombre_iniciativa' => $cdpOriginal->nombre_iniciativa,
                'programa_presupuestario' => $cdpOriginal->programa_presupuestario,
                'caracter_gasto' => $cdpOriginal->caracter_gasto,
                'medio_solicitud' => $cdpOriginal->medio_solicitud,
                'fecha_solicitud' => $cdpOriginal->fecha_solicitud,
                'moneda_compra' => $cdpOriginal->moneda_compra,
                // Monto y paridad negativos: mismo `fecha_paridad` que el
                // original, para que el service resuelva el mismo indicador
                // (misma tasa) y el monto en CLP resultante también quede
                // negativo (total negativo × paridad positiva).
                'total_moneda_compra' => -$cdpOriginal->total_moneda_compra,
                'fecha_paridad' => $cdpOriginal->fecha_paridad,
                'anio_validez' => $cdpOriginal->anio_validez,
                'requerimiento_numero' => $cdpOriginal->requerimiento_numero,
                'mercado_publico_tipo' => $cdpOriginal->mercado_publico_tipo,
                'mercado_publico_id' => $cdpOriginal->mercado_publico_id,
                'proceso_adquisicion_id' => $cdpOriginal->proceso_adquisicion_id,
                'cdp_original_id' => $cdpOriginal->id,
            ]);

            return $this->firmar->firmar($anulacion, $comentario ?? "Anulación de {$cdpOriginal->folio}");
        });
    }
}
