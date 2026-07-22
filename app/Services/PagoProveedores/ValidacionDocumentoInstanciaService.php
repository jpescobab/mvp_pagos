<?php

namespace App\Services\PagoProveedores;

use App\Enums\PagoProveedores\InstanciaRevision;
use App\Models\CasoPagoProveedor;
use App\Models\Documento;
use App\Models\Proceso;
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
     * ¿El pago está documentalmente listo para aprobarse en la instancia? El
     * gating cuenta SOLO los documentos obligatorios según el checklist del
     * proceso: no puede haber ningún obligatorio faltante y todos los
     * obligatorios presentes deben estar aprobados (`valido`) en la instancia.
     * Los documentos opcionales no bloquean.
     */
    public function todosAprobados(CasoPagoProveedor $caso, InstanciaRevision $instancia): bool
    {
        $clasificados = $this->documentosDelCaso($caso);

        if ($clasificados['faltantes'] !== []) {
            return false;
        }

        $obligatorios = $clasificados['obligatorios'];

        if ($obligatorios->isEmpty()) {
            return false;
        }

        return $obligatorios->every(
            fn (Documento $documento) => $this->estadoVigente($documento, $instancia) === 'valido',
        );
    }

    /**
     * Documentos activos vinculados al proceso del caso, clasificados contra el
     * checklist documental del proceso: obligatorios (tipo exigido por un ítem
     * obligatorio del checklist), opcionales (el resto de lo vinculado) y
     * faltantes (tipos obligatorios del checklist sin documento vinculado).
     *
     * @return array{
     *     obligatorios: Collection<int, Documento>,
     *     opcionales: Collection<int, Documento>,
     *     faltantes: list<array{tipo_documento_id: int, tipo_documento: string|null}>
     * }
     */
    public function documentosDelCaso(CasoPagoProveedor $caso): array
    {
        $proceso = $caso->proceso;

        if ($proceso === null) {
            return ['obligatorios' => collect(), 'opcionales' => collect(), 'faltantes' => []];
        }

        $documentoIds = $proceso->vinculosDocumento()
            ->where('activo', true)
            ->pluck('documento_id');

        /** @var Collection<int, Documento> $documentos */
        $documentos = Documento::query()
            ->whereIn('id', $documentoIds)
            ->with(['tipoDocumento', 'validaciones.validadoPor'])
            ->get();

        [$tipoIdsObligatorios, $tiposObligatorios] = $this->obligatoriosDelProceso($proceso);

        $obligatorios = $documentos
            ->filter(fn (Documento $d) => in_array($d->tipo_documento_id, $tipoIdsObligatorios, true))
            ->values();

        $opcionales = $documentos
            ->reject(fn (Documento $d) => in_array($d->tipo_documento_id, $tipoIdsObligatorios, true))
            ->values();

        $tiposPresentes = $documentos->pluck('tipo_documento_id')->all();

        $faltantes = array_values(array_filter(
            $tiposObligatorios,
            fn (array $tipo) => ! in_array($tipo['tipo_documento_id'], $tiposPresentes, true),
        ));

        return [
            'obligatorios' => $obligatorios,
            'opcionales' => $opcionales,
            'faltantes' => $faltantes,
        ];
    }

    /**
     * Tipos de documento obligatorios del proceso según su checklist. Devuelve
     * la lista de `tipo_documento_id` obligatorios y la lista (única por tipo)
     * con el nombre del tipo, para construir tanto la clasificación como las
     * filas faltantes. Sin checklist generado, no hay obligatorios.
     *
     * @return array{0: list<int>, 1: list<array{tipo_documento_id: int, tipo_documento: string|null}>}
     */
    private function obligatoriosDelProceso(Proceso $proceso): array
    {
        $checklist = $proceso->checklist;

        if ($checklist === null) {
            return [[], []];
        }

        $items = $checklist->items()
            ->where('tipo_requisito', 'obligatorio')
            ->with('tipoDocumento')
            ->get();

        $tipos = [];

        foreach ($items as $item) {
            $tipoId = $item->tipo_documento_id;

            if (isset($tipos[$tipoId])) {
                continue;
            }

            $tipos[$tipoId] = [
                'tipo_documento_id' => $tipoId,
                'tipo_documento' => $item->tipoDocumento?->nombre,
            ];
        }

        return [array_keys($tipos), array_values($tipos)];
    }
}
