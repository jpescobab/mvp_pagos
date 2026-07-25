<?php

namespace App\Http\Requests\Adquisiciones;

use App\Models\ProcesoAdquisicion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarProcesoAdquisicionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ProcesoAdquisicion $proceso */
        $proceso = $this->route('proceso');

        return [
            'codigo' => ['required', 'string', Rule::unique('procesos_adquisicion', 'codigo')->ignore($proceso)],
            'modalidad_id' => ['required', 'exists:modalidades_adquisicion,id'],
            'ccosto_id' => ['required', 'exists:ccostos,id'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'monto' => ['nullable', 'numeric', 'min:0'],
            'objeto' => ['required', 'string'],
        ];
    }
}
