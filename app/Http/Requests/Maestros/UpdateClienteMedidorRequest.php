<?php

namespace App\Http\Requests\Maestros;

use App\Models\ClienteMedidor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteMedidorRequest extends FormRequest
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
        /** @var ClienteMedidor $clienteMedidor */
        $clienteMedidor = $this->route('clienteMedidor');

        return [
            'numero_cliente' => ['required', 'string', 'max:255', Rule::unique('clientes_medidores', 'numero_cliente')->ignore($clienteMedidor->id)],
            'ccosto_id' => ['required', 'exists:ccostos,id'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'tipo_suministro' => ['required', 'string', 'max:255'],
            'direccion_suministro' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
