<?php

namespace App\Http\Requests\Adquisiciones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VincularOrdenCompraMercadoPublicoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proceso_adquisicion_id' => ['required', 'integer', 'exists:procesos_adquisicion,id'],
        ];
    }
}
