<?php

namespace App\Services\Reportabilidad;

use App\Models\PeriodoReportabilidad;
use Illuminate\Database\Eloquent\Model;

/**
 * Una fuente reportable sabe qué entidades internas de un período entran a un
 * corte de reportabilidad, cómo etiquetarlas y cómo serializar su estado como
 * evidencia. Cada tipo de entidad reportable (casos de pago, egresos, etc.)
 * implementa esta interfaz; el generador las recorre sin conocer sus detalles.
 */
interface FuenteReportable
{
    /**
     * Entidades reportables del período (instancias de modelo).
     *
     * @return iterable<int, Model>
     */
    public function entidades(PeriodoReportabilidad $periodo): iterable;

    /**
     * Etiqueta legible del ítem de corte que representa a la entidad.
     */
    public function etiqueta(Model $entidad): string;

    /**
     * Payload crudo con el estado de la entidad al momento del corte, que se
     * guarda como evidencia inmutable (con su hash) en el snapshot.
     *
     * @return array<string, mixed>
     */
    public function payload(Model $entidad): array;
}
