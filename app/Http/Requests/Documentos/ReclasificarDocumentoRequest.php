<?php

namespace App\Http\Requests\Documentos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReclasificarDocumentoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_documento_id' => [
                'required',
                Rule::exists('tipos_documento', 'id')->where('activo', true),
            ],
        ];
    }
}
