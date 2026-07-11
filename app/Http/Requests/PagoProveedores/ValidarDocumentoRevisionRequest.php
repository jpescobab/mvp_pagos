<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Foundation\Http\FormRequest;

class ValidarDocumentoRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:valido,rechazado'],
            'observacion' => ['nullable', 'string', 'max:2000', 'required_if:estado,rechazado'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'observacion.required_if' => 'Debe indicar el motivo del rechazo del documento.',
        ];
    }
}
