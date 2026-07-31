<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Foundation\Http\FormRequest;

class EjecutarTransicionCdpRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string'],
            'comentario' => ['nullable', 'string'],
        ];
    }
}
