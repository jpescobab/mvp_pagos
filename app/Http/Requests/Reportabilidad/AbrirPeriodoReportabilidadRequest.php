<?php

namespace App\Http\Requests\Reportabilidad;

use Illuminate\Foundation\Http\FormRequest;

class AbrirPeriodoReportabilidadRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }
}
