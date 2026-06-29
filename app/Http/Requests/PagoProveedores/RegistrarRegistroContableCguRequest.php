<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarRegistroContableCguRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'numero_registro' => ['required', 'string', 'max:255'],
            'fecha_registro' => ['required', 'date'],
            'monto' => ['required', 'numeric'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
