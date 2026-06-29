<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarRegistroPagoBancarioRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'numero_operacion' => ['required', 'string', 'max:255'],
            'fecha_pago' => ['required', 'date'],
            'monto' => ['required', 'numeric'],
            'banco' => ['nullable', 'string', 'max:255'],
        ];
    }
}
