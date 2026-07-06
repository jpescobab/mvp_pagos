<?php

namespace App\Http\Requests\Adquisiciones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BuscarOrdenCompraMercadoPublicoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50'],
        ];
    }
}
