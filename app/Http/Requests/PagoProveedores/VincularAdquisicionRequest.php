<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VincularAdquisicionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proceso_adquisicion_id' => ['required', 'integer', 'exists:procesos_adquisicion,id'],
        ];
    }
}
