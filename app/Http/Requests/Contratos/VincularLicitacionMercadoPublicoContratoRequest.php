<?php

namespace App\Http\Requests\Contratos;

use Illuminate\Foundation\Http\FormRequest;

class VincularLicitacionMercadoPublicoContratoRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'licitacion_mercado_publico_id' => ['required', 'exists:licitaciones_mercado_publico,id'],
        ];
    }
}
