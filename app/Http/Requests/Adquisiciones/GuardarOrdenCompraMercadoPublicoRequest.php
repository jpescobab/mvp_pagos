<?php

namespace App\Http\Requests\Adquisiciones;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GuardarOrdenCompraMercadoPublicoRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:50', 'unique:ordenes_compra_mercado_publico,codigo'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'proceso_adquisicion_id' => ['nullable', 'integer', 'exists:procesos_adquisicion,id'],
        ];
    }
}
