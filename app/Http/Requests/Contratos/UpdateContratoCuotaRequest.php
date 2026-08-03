<?php

namespace App\Http\Requests\Contratos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContratoCuotaRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'fecha_vencimiento' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
        ];
    }
}
