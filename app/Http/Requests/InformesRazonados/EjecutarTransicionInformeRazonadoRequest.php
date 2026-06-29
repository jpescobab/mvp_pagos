<?php

namespace App\Http\Requests\InformesRazonados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EjecutarTransicionInformeRazonadoRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', Rule::in(['enviar_a_revision', 'aprobar', 'rechazar', 'publicar'])],
            'comentario' => ['nullable', 'string'],
        ];
    }
}
