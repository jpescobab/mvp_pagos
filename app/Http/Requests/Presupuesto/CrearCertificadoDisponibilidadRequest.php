<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Foundation\Http\FormRequest;

class CrearCertificadoDisponibilidadRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'presupuesto_id' => ['required', 'exists:presupuestos,id'],
            'fecha_solicitud' => ['nullable', 'date'],
            'tipo_gasto' => ['required', 'in:GO,INI'],
            'codigo_iniciativa' => ['required_if:tipo_gasto,INI', 'nullable', 'string'],
            'nombre' => ['required', 'string'],
            'nombre_iniciativa' => ['required_if:tipo_gasto,INI', 'nullable', 'string'],
            'programa_presupuestario' => ['nullable', 'string'],
            'caracter_gasto' => ['required', 'in:transitorio,permanente'],
            'medio_solicitud' => ['nullable', 'in:Requerimiento,Oficio,Otro'],
            'moneda_compra' => ['nullable', 'in:CLP,UF,USD'],
            'total_moneda_compra' => ['required', 'numeric'],
            // `fecha_paridad` es la única entrada del usuario para la paridad —
            // `paridad` y `monto` los resuelve y calcula el service (ver
            // CrearBorradorCertificadoDisponibilidadService), nunca vienen del
            // cliente.
            'fecha_paridad' => ['required_if:moneda_compra,UF,USD', 'nullable', 'date'],
            'anio_validez' => ['required', 'integer', 'min:2000'],
            'requerimiento_numero' => ['nullable', 'string'],
            'mercado_publico_tipo' => ['nullable', 'in:orden_compra,licitacion'],
            'mercado_publico_id' => ['nullable', 'integer'],
            'proceso_adquisicion_id' => ['nullable', 'exists:procesos_adquisicion,id'],
        ];
    }
}
