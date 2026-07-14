<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClasificarTipoProcesoPagoRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tipo_proceso_pago_id' => [
                'required',
                'integer',
                Rule::exists('tipos_proceso_pago', 'id')->where('activo', true),
            ],
        ];
    }
}
