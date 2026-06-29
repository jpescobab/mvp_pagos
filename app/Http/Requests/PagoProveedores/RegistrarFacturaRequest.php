<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarFacturaRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'folio' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric'],
            'fecha_emision' => ['required', 'date'],
        ];
    }
}
