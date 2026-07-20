<?php

namespace App\Services\PagoProveedores;

use App\Models\CasoPagoProveedor;

class PreparacionEgresoPresenter
{
    /**
     * Los 4 criterios de disposición del caso para el panel "Preparación
     * para Asignar Egreso" — única fuente de esta lógica: ListoParaEgresoResolver
     * delega en este método para su respuesta booleana.
     *
     * @return list<array{criterio: string, etiqueta: string, cumplido: bool, detalle: string}>
     */
    public function criterios(CasoPagoProveedor $caso): array
    {
        return [
            $this->tipoProceso($caso),
            $this->traspasoCgu($caso),
            $this->checklistDocumental($caso),
            $this->proveedorIdentificado($caso),
        ];
    }

    /**
     * @return array{criterio: string, etiqueta: string, cumplido: bool, detalle: string}
     */
    private function tipoProceso(CasoPagoProveedor $caso): array
    {
        return [
            'criterio' => 'tipo_proceso',
            'etiqueta' => 'Tipo de proceso',
            'cumplido' => $caso->proceso?->tipo_proceso_pago_id !== null,
            'detalle' => $caso->proceso?->tipoProcesoPago->nombre ?? 'Sin clasificar',
        ];
    }

    /**
     * @return array{criterio: string, etiqueta: string, cumplido: bool, detalle: string}
     */
    private function traspasoCgu(CasoPagoProveedor $caso): array
    {
        if (! $caso->requiereTraspasoCgu()) {
            return [
                'criterio' => 'traspaso_cgu',
                'etiqueta' => 'Traspaso (CGU)',
                'cumplido' => true,
                'detalle' => 'No requiere traspaso',
            ];
        }

        $registro = $caso->registrosContablesCgu->first();

        return [
            'criterio' => 'traspaso_cgu',
            'etiqueta' => 'Traspaso (CGU)',
            'cumplido' => $registro !== null || $caso->sgf_numero_traspaso !== null,
            'detalle' => $registro->numero_registro ?? $caso->sgf_numero_traspaso ?? 'Sin registrar',
        ];
    }

    /**
     * Un checklist resuelto sin ítems obligatorios cumple este criterio:
     * `every()` sobre una colección vacía es verdad vacua en Laravel, y no
     * hay ningún requisito obligatorio pendiente por definición. El texto
     * de detalle distingue explícitamente ese caso ("Sin ítems
     * obligatorios") del caso en que el checklist directamente no se ha
     * resuelto todavía ("Sin checklist generado") — antes eran
     * indistinguibles en el frontend, lo que dejaba casos como los de tipo
     * de proceso "Remesa" (sin documentos obligatorios) atascados como
     * incompletos pese a estar correctamente satisfechos.
     *
     * @return array{criterio: string, etiqueta: string, cumplido: bool, detalle: string}
     */
    private function checklistDocumental(CasoPagoProveedor $caso): array
    {
        $checklist = $caso->proceso?->checklist;

        if ($checklist === null) {
            return [
                'criterio' => 'checklist_documental',
                'etiqueta' => 'Checklist documental',
                'cumplido' => false,
                'detalle' => 'Sin checklist generado',
            ];
        }

        $obligatorios = $checklist->items->where('tipo_requisito', 'obligatorio');
        $conDocumento = $obligatorios->filter(fn ($item) => $item->documento_id !== null)->count();

        return [
            'criterio' => 'checklist_documental',
            'etiqueta' => 'Checklist documental',
            'cumplido' => $obligatorios->every(fn ($item) => $item->documento_id !== null),
            'detalle' => $obligatorios->isEmpty()
                ? 'Sin ítems obligatorios'
                : "{$conDocumento} / {$obligatorios->count()} obligatorios",
        ];
    }

    /**
     * @return array{criterio: string, etiqueta: string, cumplido: bool, detalle: string}
     */
    private function proveedorIdentificado(CasoPagoProveedor $caso): array
    {
        return [
            'criterio' => 'proveedor',
            'etiqueta' => 'Proveedor identificado',
            'cumplido' => $caso->proveedor_id !== null,
            'detalle' => $caso->proveedor->nombre ?? 'No identificado',
        ];
    }
}
