<?php

namespace App\Services\Integraciones;

use App\Models\TrabajoIntegracion;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Elimina una corrida de importación SGF (`trabajo_integracion`) mientras no
 * esté en progreso. Nunca borra snapshots, casos, procesos ni auditoría
 * preexistente: `snapshots_datos_externos.trabajo_integracion_id` es
 * `nullOnDelete`, así que un snapshot ya capturado sobrevive intacto, solo
 * pierde la referencia a qué corrida en particular lo generó. Solo borra el
 * trabajo y sus artefactos propios del intento (ejecuciones + pasos,
 * solicitudes API), y deja registrada la eliminación en la auditoría de
 * acciones.
 */
class EliminarImportacionSgfService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function eliminar(TrabajoIntegracion $trabajo, User $user): void
    {
        if (! $trabajo->puedeEliminarse()) {
            throw new RuntimeException(
                'Esta importación no se puede eliminar porque todavía está en progreso.'
            );
        }

        $metadata = [
            'trabajo_integracion_id' => $trabajo->id,
            'tipo' => $trabajo->tipo,
            'estado' => $trabajo->estado,
            'mecanismo' => $trabajo->mecanismo,
        ];

        DB::transaction(function () use ($trabajo, $user, $metadata): void {
            // Se audita la eliminación antes de borrar (dentro de la misma
            // transacción): ambos efectos son atómicos.
            $this->auditLogger->log('importaciones_sgf.eliminada', $trabajo, metadata: $metadata, user: $user);

            // Los pasos cascadean al borrar cada ejecución (FK on delete
            // cascade); las ejecuciones y solicitudes son NO ACTION hacia el
            // trabajo, así que se borran explícitamente antes que el trabajo.
            $trabajo->ejecucionesAutomatizacionNavegador()->delete();
            $trabajo->solicitudesApiExternas()->delete();
            $trabajo->delete();
        });
    }
}
