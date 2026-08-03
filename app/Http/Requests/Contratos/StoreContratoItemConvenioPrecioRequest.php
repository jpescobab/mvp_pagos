<?php

namespace App\Http\Requests\Contratos;

use Illuminate\Foundation\Http\FormRequest;

class StoreContratoItemConvenioPrecioRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'descripcion' => ['required', 'string'],
            'unidad_medida' => ['nullable', 'string', 'max:255'],
            'precio_unitario' => ['required', 'numeric', 'min:0'],
            'moneda' => ['nullable', 'string', 'max:10'],
            'vigente_desde' => ['nullable', 'date'],
            'vigente_hasta' => ['nullable', 'date', 'after_or_equal:vigente_desde'],
        ];
    }
}
