<?php

namespace App\Http\Requests\Adquisiciones;

use Illuminate\Foundation\Http\FormRequest;

class CrearProcesoAdquisicionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'unique:procesos_adquisicion,codigo'],
            'modalidad_id' => ['required', 'exists:modalidades_adquisicion,id'],
            'ccosto_id' => ['required', 'exists:ccostos,id'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'monto' => ['nullable', 'numeric', 'min:0'],
            'objeto' => ['required', 'string'],
        ];
    }
}
