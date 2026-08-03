<?php

namespace App\Http\Requests\Contratos;

use Illuminate\Foundation\Http\FormRequest;

class StoreContratoRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'id_institucional' => ['required', 'integer', 'unique:contratos,id_institucional'],
            'modalidad_compra' => ['required', 'in:licitacion,trato_directo,fuera_de_portal'],
            'id_proceso_mp' => ['nullable', 'string', 'max:255'],
            'tipo_contrato' => ['required', 'in:contrato,convenio_precio,orden_compra,arriendo'],
            'referencia' => ['required', 'string'],
            'fecha_inicio_vigencia' => ['required', 'date'],
            'fecha_fin_vigencia' => ['required', 'date', 'after_or_equal:fecha_inicio_vigencia'],
            'materia' => ['nullable', 'string', 'max:255'],
            'submateria' => ['nullable', 'string', 'max:255'],
            'tiene_convenio_precio' => ['required', 'boolean'],
            'tiene_calendario_pago' => ['required', 'boolean'],
            'periodicidad_pago' => ['required_if:tiene_calendario_pago,true', 'nullable', 'in:mensual,bimestral,trimestral,semestral,anual,unica'],
            'monto_total' => ['nullable', 'numeric', 'min:0'],
            'proveedor_id' => ['required_without:proveedor', 'nullable', 'exists:proveedores,id'],
            'proveedor' => ['required_without:proveedor_id', 'nullable', 'array'],
            'proveedor.rutproveedor' => ['required_with:proveedor', 'string'],
            'proveedor.nombre' => ['nullable', 'string'],
            'proceso_adquisicion_id' => ['nullable', 'exists:procesos_adquisicion,id'],
            'licitacion_mercado_publico_id' => ['nullable', 'exists:licitaciones_mercado_publico,id'],
        ];
    }
}
