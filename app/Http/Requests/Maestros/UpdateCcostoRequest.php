<?php

namespace App\Http\Requests\Maestros;

use App\Models\Ccosto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCcostoRequest extends FormRequest
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
        /** @var Ccosto $ccosto */
        $ccosto = $this->route('ccosto');

        return [
            'codigo' => ['required', 'string', 'max:255', Rule::unique('ccostos', 'codigo')->ignore($ccosto->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'cfinanciero_id' => ['required', 'exists:cfinancieros,id'],
            'cod_edificio' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ];
    }
}
