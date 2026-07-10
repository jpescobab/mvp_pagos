<?php

namespace App\Services\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\User;
use App\Models\ValidacionDocumento;
use Illuminate\Support\Collection;

/**
 * Registra y resuelve la validación de documentos por instancia de revisión.
 * Cada instancia (finanzas | zonal) valida los documentos de forma
 * independiente: una validación emitida por una instancia no altera el estado
 * vigente del documento para otra instancia, y el historial completo se
 * conserva (documentos-expediente-variable).
 */
class ValidacionDocumentoInstanciaService
{
    /**
     * Registra un evento de validación de un documento dentro de una instancia.
     */
    public function validar(
        Documento $documento,
        InstanciaRevision $instancia,
        string $estado,
        ?string $observacion,
        User $user,
    ): ValidacionDocumento {
        return $documento->validaciones()->create([
            'estado' => $estado,
            'instancia' => $instancia,
            'observacion' => $observacion !== null && trim($observacion) !== '' ? $observacion : null,
            'validado_por' => $user->id,
            'validado_en' => now(),
        ]);
    }

    /**
     * Estado vigente de un documento para una instancia dada: el del evento de
     * validación más reciente emitido por esa instancia; `pendiente` si no hay.
     */
    public function estadoVigente(Documento $documento, InstanciaRevision $instancia): string
    {
        $ultima = $documento->validaciones
            ->where('instancia', $instancia)
            ->sortByDesc('id')
            ->first();

        if ($ultima === null) {
            return 'pendiente';
        }

        return $ultima->estado;
    }

    /**
     * ¿Todos los documentos vinculados al proceso del caso están aprobados
     * (`valido`) en la instancia indicada?
     */
    public function todosAprobados(CasoPagoProveedor $caso, InstanciaRevision $instancia): bool
    {
        $documentos = $this->documentosDelCaso($caso);

        if ($documentos->isEmpty()) {
            return false;
        }

        return $documentos->every(
            fn (Documento $documento) => $this->estadoVigente($documento, $instancia) === 'valido',
        );
    }

    /**
     * Documentos activos vinculados al proceso del caso, con sus validaciones.
     *
     * @return Collection<int, Documento>
     */
    public function documentosDelCaso(CasoPagoProveedor $caso): Collection
    {
        $proceso = $caso->proceso;

        if ($proceso === null) {
            return collect();
        }

        $documentoIds = $proceso->vinculosDocumento()
            ->where('activo', true)
            ->pluck('documento_id');

        if ($documentoIds->isEmpty()) {
            return collect();
        }

        return Documento::whereIn('id', $documentoIds)
            ->with(['tipoDocumento', 'validaciones.validadoPor'])
            ->get();
    }
}
