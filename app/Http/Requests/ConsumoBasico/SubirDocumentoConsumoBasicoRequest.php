<?php

namespace App\Http\Requests\ConsumoBasico;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubirDocumentoConsumoBasicoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
