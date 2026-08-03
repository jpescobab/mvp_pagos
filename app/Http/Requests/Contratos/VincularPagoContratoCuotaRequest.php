<?php

namespace App\Http\Requests\Contratos;

use Illuminate\Foundation\Http\FormRequest;

class VincularPagoContratoCuotaRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'caso_pago_proveedor_id' => ['required', 'exists:casos_pago_proveedor,id'],
        ];
    }
}
