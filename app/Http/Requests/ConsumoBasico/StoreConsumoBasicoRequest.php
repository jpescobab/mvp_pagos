<?php

namespace App\Http\Requests\ConsumoBasico;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsumoBasicoRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'cliente_medidor_id' => ['required_without:cliente_medidor', 'nullable', 'exists:clientes_medidores,id'],
            'cliente_medidor' => ['required_without:cliente_medidor_id', 'nullable', 'array'],
            'cliente_medidor.numero_cliente' => ['required_with:cliente_medidor', 'string', 'max:255'],
            'cliente_medidor.ccosto_id' => ['required_with:cliente_medidor', 'exists:ccostos,id'],
            'cliente_medidor.tipo_suministro' => ['required_with:cliente_medidor', 'string', 'max:255'],
            'cliente_medidor.direccion_suministro' => ['nullable', 'string', 'max:255'],
            'numero_documento' => ['required', 'string', 'max:255'],
            'numero_medidor' => ['required', 'string', 'max:255'],
            'fecha_inicio_lectura' => ['required', 'date'],
            'fecha_fin_lectura' => ['required', 'date', 'after_or_equal:fecha_inicio_lectura'],
            'fecha_emision' => ['required', 'date'],
            'fecha_vencimiento' => ['required', 'date'],
            'consumo' => ['nullable', 'numeric'],
            'tarifa' => ['nullable', 'string', 'max:255'],
            'lectura_estimada' => ['required', 'boolean'],
            'monto_neto' => ['required', 'numeric'],
            'iva' => ['required', 'numeric'],
            'monto_exento' => ['nullable', 'numeric'],
            'saldo_anterior' => ['nullable', 'numeric'],
            'monto_total' => ['required', 'numeric'],
        ];
    }
}
