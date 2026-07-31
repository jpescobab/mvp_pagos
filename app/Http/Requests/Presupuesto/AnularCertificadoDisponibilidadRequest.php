<?php

namespace App\Http\Requests\Presupuesto;

use Illuminate\Foundation\Http\FormRequest;

class AnularCertificadoDisponibilidadRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'comentario' => ['nullable', 'string'],
        ];
    }
}
