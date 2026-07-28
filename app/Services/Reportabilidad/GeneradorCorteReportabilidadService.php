<?php

namespace App\Services\Reportabilidad;

use App\Exceptions\CorteReportabilidadException;
use App\Models\CorteReportabilidad;
use Illuminate\Support\Facades\DB;

/**
 * Genera el contenido de un corte de reportabilidad: recorre las fuentes
 * reportables del período y, por cada entidad, crea un ítem y captura su
 * snapshot de evidencia. Orquesta las primitivas de `CorteReportabilidadService`
 * dentro de una transacción; la recolección y serialización vive en las fuentes.
 */
class GeneradorCorteReportabilidadService
{
    /**
     * @var list<FuenteReportable>
     */
    private array $fuentes;

    public function __construct(
        private readonly CorteReportabilidadService $cortes,
        FuenteReportableCasosPagoProveedor $casosPagoProveedor,
    ) {
        // El orden de las fuentes no importa; agregar una nueva entidad
        // reportable es sumar su fuente a esta lista.
        $this->fuentes = [$casosPagoProveedor];
    }

    /**
     * Puebla el corte (en borrador) con un ítem + snapshot por cada entidad
     * reportable del período. Regenerar reemplaza el contenido previo.
     */
    public function generar(CorteReportabilidad $corte): void
    {
        if ($corte->estaPublicado()) {
            throw CorteReportabilidadException::corteYaPublicado();
        }

        DB::transaction(function () use ($corte): void {
            $corte->snapshots()->delete();
            $corte->items()->delete();

            $periodo = $corte->periodoReportabilidad;

            foreach ($this->fuentes as $fuente) {
                foreach ($fuente->entidades($periodo) as $entidad) {
                    $item = $this->cortes->agregarItem($corte, $entidad, $fuente->etiqueta($entidad));
                    $this->cortes->capturarSnapshot($corte, $fuente->payload($entidad), $item);
                }
            }
        });
    }
}
