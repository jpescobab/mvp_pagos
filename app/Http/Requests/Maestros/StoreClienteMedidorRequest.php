<?php

namespace App\Http\Requests\Maestros;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteMedidorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('core_institucional.administrar');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'numero_cliente' => ['required', 'string', 'max:255', 'unique:clientes_medidores,numero_cliente'],
            'ccosto_id' => ['required', 'exists:ccostos,id'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'tipo_suministro' => ['required', 'string', 'max:255'],
            'direccion_suministro' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
