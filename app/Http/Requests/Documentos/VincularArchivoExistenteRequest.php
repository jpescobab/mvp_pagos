<?php

namespace App\Http\Requests\Documentos;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VincularArchivoExistenteRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ruta_archivo' => ['required', 'string'],
            'tipo_documento_id' => [
                'required',
                Rule::exists('tipos_documento', 'id')->where('activo', true),
            ],
        ];
    }
}
