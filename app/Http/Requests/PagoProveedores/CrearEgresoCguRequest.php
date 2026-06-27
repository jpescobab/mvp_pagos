<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Foundation\Http\FormRequest;

class CrearEgresoCguRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('pago_proveedores.registrar_egreso');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'numero_egreso' => ['required', 'string', 'unique:egresos_cgu,numero_egreso'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string'],
            'casos' => ['required', 'array', 'min:1'],
            'casos.*.caso_pago_proveedor_id' => ['required', 'exists:casos_pago_proveedor,id'],
            'casos.*.monto' => ['required', 'numeric', 'min:0'],
        ];
    }
}
