<?php

namespace App\Http\Requests\Documentos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ValidarDocumentoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estado' => ['required', 'in:valido,rechazado'],
            'observacion' => ['nullable', 'string', 'required_if:estado,rechazado'],
        ];
    }
}
