<?php

namespace App\Http\Requests\Contratos;

use Illuminate\Foundation\Http\FormRequest;

class VincularProcesoAdquisicionContratoRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'proceso_adquisicion_id' => ['required', 'exists:procesos_adquisicion,id'],
        ];
    }
}
