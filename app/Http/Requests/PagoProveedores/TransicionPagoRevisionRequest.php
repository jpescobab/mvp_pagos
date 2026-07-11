<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Foundation\Http\FormRequest;

class TransicionPagoRevisionRequest extends FormRequest
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
            'accion' => ['required', 'in:aprobar,rechazar,devolver'],
            'comentario' => ['nullable', 'string', 'max:2000', 'required_if:accion,rechazar', 'required_if:accion,devolver'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'comentario.required_if' => 'Debe indicar un comentario para rechazar o devolver el pago.',
        ];
    }
}
