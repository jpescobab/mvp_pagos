<?php

namespace App\Http\Requests\PagoProveedores;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDetalleFacturaRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tipo_compra_id' => ['required', 'exists:tipos_compra,id'],
            'ccosto_id' => ['required', 'exists:ccostos,id'],
            'cantidad' => ['required', 'numeric', 'min:0'],
            'unidad_medida' => ['nullable', 'string', 'max:50'],
            'monto' => ['required', 'numeric', 'min:0'],
        ];
    }
}
