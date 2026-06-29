<?php

namespace App\Http\Requests\InformesRazonados;

use Illuminate\Foundation\Http\FormRequest;

class IniciarEjecucionInformeRazonadoRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'definicion_informe_razonado_id' => ['required', 'integer', 'exists:definiciones_informe_razonado,id'],
            'corte_reportabilidad_id' => ['required', 'integer', 'exists:cortes_reportabilidad,id'],
        ];
    }
}
