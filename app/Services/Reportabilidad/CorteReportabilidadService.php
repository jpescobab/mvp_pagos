<?php

namespace App\Services\Reportabilidad;

use App\Exceptions\CorteReportabilidadException;
use App\Models\CorteReportabilidad;
use App\Models\CorteReportabilidadItem;
use App\Models\PeriodoReportabilidad;
use App\Models\SnapshotCorteReportabilidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CorteReportabilidadService
{
    public function abrirPeriodo(string $codigo, string $fechaInicio, string $fechaFin): PeriodoReportabilidad
    {
        return PeriodoReportabilidad::create([
            'codigo' => $codigo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'estado' => 'abierto',
        ]);
    }

    public function crearCorte(PeriodoReportabilidad $periodo): CorteReportabilidad
    {
        return $periodo->cortesReportabilidad()->create([
            'fecha_corte' => now(),
            'estado' => 'borrador',
        ]);
    }

    public function agregarItem(CorteReportabilidad $corte, Model $vinculable, string $etiqueta): CorteReportabilidadItem
    {
        if ($corte->estaPublicado()) {
            throw CorteReportabilidadException::corteYaPublicado();
        }

        return $corte->items()->create([
            'vinculable_type' => $vinculable->getMorphClass(),
            'vinculable_id' => $vinculable->getKey(),
            'etiqueta' => $etiqueta,
            'incluido_en' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payloadCrudo
     */
    public function capturarSnapshot(CorteReportabilidad $corte, array $payloadCrudo, ?CorteReportabilidadItem $item = null): SnapshotCorteReportabilidad
    {
        if ($corte->estaPublicado()) {
            throw CorteReportabilidadException::corteYaPublicado();
        }

        return $corte->snapshots()->create([
            'corte_reportabilidad_item_id' => $item?->id,
            'payload_crudo' => $payloadCrudo,
            'hash' => hash('sha256', json_encode($payloadCrudo, JSON_THROW_ON_ERROR)),
            'capturado_en' => now(),
        ]);
    }

    public function publicarCorte(CorteReportabilidad $corte, ?User $usuario = null): CorteReportabilidad
    {
        $usuario ??= Auth::user();

        if ($usuario === null || ! $usuario->can('reportabilidad.publicar_corte')) {
            throw CorteReportabilidadException::sinPermiso('reportabilidad.publicar_corte');
        }

        $corte->update([
            'estado' => 'publicado',
            'publicado_por' => $usuario->id,
            'publicado_en' => now(),
        ]);

        return $corte->refresh();
    }
}
